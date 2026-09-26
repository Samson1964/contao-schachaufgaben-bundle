<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Import;

/**
 * Eine geöffnete Lichess-CSV, die sich zeilenweise lesen und an einer
 * gemerkten Stelle fortsetzen lässt.
 *
 * Das Fortsetzen braucht der Import im Backend: Jeder Aufruf verarbeitet nur
 * einige Sekunden lang Zeilen, merkt sich die Byte-Position und springt beim
 * nächsten Aufruf per fseek() dorthin. Die Kopfzeile wird dabei jedes Mal neu
 * gelesen, damit die Spaltenzuordnung stimmt.
 *
 * Die Spalten werden über die Kopfzeile zugeordnet, nicht über ihre Position,
 * damit neue Spalten bei Lichess (etwa „DailyDate" seit 2026) nicht stören.
 */
class LichessCsvDatei
{
	/**
	 * Zuordnung der Lichess-Spalten zu den Feldern von tl_schachaufgaben.
	 */
	private const SPALTEN = array(
		'PuzzleId'        => 'lichessId',
		'FEN'             => 'fen',
		'Moves'           => 'zuege',
		'Rating'          => 'wertung',
		'RatingDeviation' => 'wertungAbweichung',
		'Popularity'      => 'beliebtheit',
		'NbPlays'         => 'spiele',
		'Themes'          => 'motive',
		'GameUrl'         => 'partieUrl',
		'OpeningTags'     => 'eroeffnung',
	);

	/**
	 * Spalten, ohne die eine Aufgabe nicht spielbar ist. Die übrigen sind
	 * Zusatzangaben und dürfen in der Datei fehlen.
	 */
	private const PFLICHTSPALTEN = array('PuzzleId', 'FEN', 'Moves', 'Rating');

	/**
	 * @var resource
	 */
	private $handle;

	private bool $standardeingabe;

	/**
	 * @var array<int, string>
	 */
	private array $kopf;

	/**
	 * @var array<string, int>
	 */
	private array $index;

	private int $zeile = 1;

	/**
	 * Öffnet die Datei und liest die Kopfzeile.
	 *
	 * @param string $pfad Pfad zur entpackten CSV-Datei, oder „-" für die
	 *                     Standardeingabe (etwa „zstd -dc datei.csv.zst |")
	 *
	 * @throws \RuntimeException Wenn die Datei nicht lesbar ist, noch gepackt
	 *                           ist, leer ist oder Pflichtspalten fehlen
	 */
	public function __construct(string $pfad)
	{
		$endung = strtolower(pathinfo($pfad, PATHINFO_EXTENSION));

		if ('zst' === $endung || 'gz' === $endung) {
			throw new \RuntimeException('Die Datei ist noch gepackt. Bitte vorher entpacken (zstd -d bzw. gunzip) oder auf der Konsole per „zstd -dc datei.csv.zst |" mit dem Pfad „-" durchreichen.');
		}

		$this->standardeingabe = '-' === $pfad;
		$handle = $this->standardeingabe ? fopen('php://stdin', 'r') : @fopen($pfad, 'r');

		if (false === $handle) {
			throw new \RuntimeException(sprintf('Die Datei „%s" lässt sich nicht öffnen.', $pfad));
		}

		$this->handle = $handle;
		$kopf = $this->csvZeile();

		if (null === $kopf) {
			$this->schliessen();

			throw new \RuntimeException('Die Datei ist leer.');
		}

		// Ein UTF-8-BOM würde sonst am Namen der ersten Spalte kleben
		$kopf[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $kopf[0]);
		$this->kopf = array_map('strval', $kopf);
		$this->index = array_flip($this->kopf);
		$fehlend = array_diff(self::PFLICHTSPALTEN, $this->kopf);

		if (array() !== $fehlend) {
			$this->schliessen();

			throw new \RuntimeException(sprintf('Es fehlen die Spalten %s. Ist das wirklich die Aufgabensammlung von Lichess?', implode(', ', $fehlend)));
		}
	}

	/**
	 * Setzt das Lesen an einer früher gemerkten Stelle fort.
	 *
	 * @param int $position Byte-Position aus position(); 0 bedeutet „direkt
	 *                      nach der Kopfzeile" und lässt alles unverändert
	 * @param int $zeile    Zeilennummer der zuletzt gelesenen Zeile, damit die
	 *                      Nummerierung in Fehlermeldungen weiterläuft
	 *
	 * @throws \RuntimeException Bei der Standardeingabe, die kein Springen erlaubt,
	 *                           oder wenn die Position hinter dem Dateiende liegt
	 */
	public function springen(int $position, int $zeile): void
	{
		if ($position <= 0) {
			return;
		}

		if ($this->standardeingabe || 0 !== fseek($this->handle, $position) || $position > (int) fstat($this->handle)['size']) {
			throw new \RuntimeException('An der gemerkten Stelle kann nicht weitergelesen werden. Wurde die Datei inzwischen ausgetauscht?');
		}

		$this->zeile = $zeile;
	}

	/**
	 * Liest die nächste Aufgabe.
	 *
	 * Leerzeilen werden übersprungen. Zeilen mit falscher Spaltenzahl kommen
	 * mit dem Schlüssel „_fehler", damit der Aufrufer sie zählen und melden
	 * kann, statt den Import abzubrechen.
	 *
	 * @return array{0: int, 1: array<string, int|string>}|null Zeilennummer
	 *                                                          und Aufgabe mit
	 *                                                          den Feldnamen von
	 *                                                          tl_schachaufgaben,
	 *                                                          oder null am Dateiende
	 */
	public function naechste(): ?array
	{
		while (null !== ($werte = $this->csvZeile())) {
			++$this->zeile;

			if (array(null) === $werte) {
				continue;
			}

			if (\count($werte) !== \count($this->kopf)) {
				return array($this->zeile, array('_fehler' => sprintf('%d statt %d Spalten', \count($werte), \count($this->kopf))));
			}

			return array($this->zeile, $this->zuordnen($werte));
		}

		return null;
	}

	/**
	 * Liefert die aktuelle Byte-Position zum späteren Fortsetzen.
	 *
	 * @return int Position hinter der zuletzt gelesenen Zeile
	 */
	public function position(): int
	{
		return (int) ftell($this->handle);
	}

	/**
	 * Liefert die Nummer der zuletzt gelesenen Zeile (Kopfzeile = 1).
	 *
	 * @return int Die Zeilennummer
	 */
	public function zeile(): int
	{
		return $this->zeile;
	}

	/**
	 * Liefert die Dateigröße für die Fortschrittsanzeige.
	 *
	 * @return int|null Größe in Bytes, oder null bei der Standardeingabe
	 */
	public function groesse(): ?int
	{
		return $this->standardeingabe ? null : (int) fstat($this->handle)['size'];
	}

	/**
	 * Schließt die Datei. Die Standardeingabe bleibt offen.
	 */
	public function schliessen(): void
	{
		if (!$this->standardeingabe && \is_resource($this->handle)) {
			fclose($this->handle);
		}
	}

	/**
	 * Liest eine CSV-Zeile.
	 *
	 * Das Escape-Zeichen wird ausdrücklich leer übergeben: PHP 8.4 meldet den
	 * Standardwert als veraltet, und die Lichess-Datei kennt ohnehin nur die
	 * CSV-übliche Verdopplung von Anführungszeichen.
	 *
	 * @return array<int, string|null>|null Die Werte der Zeile, [null] bei
	 *                                      einer Leerzeile, null am Dateiende
	 */
	private function csvZeile(): ?array
	{
		$werte = fgetcsv($this->handle, 0, ',', '"', '');

		return false === $werte ? null : $werte;
	}

	/**
	 * Benennt die Werte einer Zeile auf die Tabellenfelder um.
	 *
	 * @param array<int, string|null> $werte Die Werte in Dateireihenfolge
	 *
	 * @return array<string, int|string> Feldname => Wert; fehlende
	 *                                   Zusatzspalten werden als leerer
	 *                                   Text bzw. 0 geliefert
	 */
	private function zuordnen(array $werte): array
	{
		$aufgabe = array();

		foreach (self::SPALTEN as $spalte => $feld) {
			$aufgabe[$feld] = isset($this->index[$spalte]) ? trim((string) $werte[$this->index[$spalte]]) : '';
		}

		foreach (array('wertung', 'wertungAbweichung', 'beliebtheit', 'spiele') as $feld) {
			$aufgabe[$feld] = (int) $aufgabe[$feld];
		}

		return $aufgabe;
	}
}
