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
 * Liest die Aufgabensammlung von Lichess (lichess_db_puzzle.csv).
 *
 * Die Datei hat mehrere Millionen Zeilen. Sie wird deshalb Zeile für Zeile
 * gelesen und als Generator weitergereicht, statt sie in den Speicher zu laden.
 * Die Spalten werden über die Kopfzeile zugeordnet, nicht über ihre Position,
 * damit neue Spalten bei Lichess den Import nicht verschieben.
 */
class LichessCsvLeser
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
	 * Liest die Datei und liefert jede Datenzeile als Feld-Array.
	 *
	 * Die Werte sind bereits auf die Felder von tl_schachaufgaben umbenannt;
	 * Zahlenfelder kommen als int. Leere Zeilen werden übersprungen. Zeilen
	 * mit falscher Spaltenzahl werden mit dem Schlüssel „_fehler" geliefert,
	 * damit der Aufrufer sie zählen und melden kann, statt den Import
	 * abzubrechen.
	 *
	 * @param string $pfad Pfad zur entpackten CSV-Datei, oder „-" für die
	 *                     Standardeingabe (etwa „zstd -dc datei.csv.zst |")
	 *
	 * @throws \RuntimeException Wenn die Datei nicht lesbar ist, noch mit
	 *                           Zstandard gepackt ist oder Pflichtspalten fehlen
	 *
	 * @return \Generator<int, array<string, int|string>> Schlüssel ist die
	 *                                                     Zeilennummer in der Datei
	 */
	public function lesen(string $pfad): \Generator
	{
		if ('.zst' === strtolower(substr($pfad, -4))) {
			throw new \RuntimeException('Die Datei ist noch mit Zstandard gepackt. Bitte vorher mit „zstd -d" entpacken oder per „zstd -dc datei.csv.zst |" mit dem Pfad „-" durchreichen.');
		}

		$handle = '-' === $pfad ? fopen('php://stdin', 'r') : @fopen($pfad, 'r');

		if (false === $handle) {
			throw new \RuntimeException(sprintf('Die Datei „%s" lässt sich nicht öffnen.', $pfad));
		}

		try {
			$kopf = $this->naechsteZeile($handle);

			if (null === $kopf) {
				throw new \RuntimeException('Die Datei ist leer.');
			}

			// Ein UTF-8-BOM würde sonst am Namen der ersten Spalte kleben
			$kopf[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $kopf[0]);
			$index = array_flip($kopf);
			$fehlend = array_diff(self::PFLICHTSPALTEN, $kopf);

			if (array() !== $fehlend) {
				throw new \RuntimeException(sprintf('Es fehlen die Spalten %s. Ist das wirklich die Aufgabensammlung von Lichess?', implode(', ', $fehlend)));
			}

			$zeilennummer = 1;

			while (null !== ($werte = $this->naechsteZeile($handle))) {
				++$zeilennummer;

				if (array(null) === $werte) {
					continue;
				}

				if (\count($werte) !== \count($kopf)) {
					yield $zeilennummer => array('_fehler' => sprintf('%d statt %d Spalten', \count($werte), \count($kopf)));
					continue;
				}

				yield $zeilennummer => $this->zuordnen($werte, $index);
			}
		} finally {
			if ('-' !== $pfad) {
				fclose($handle);
			}
		}
	}

	/**
	 * Liest eine CSV-Zeile.
	 *
	 * Das Escape-Zeichen wird ausdrücklich leer übergeben: PHP 8.4 meldet den
	 * Standardwert als veraltet, und die Lichess-Datei kennt ohnehin nur die
	 * CSV-übliche Verdopplung von Anführungszeichen.
	 *
	 * @param resource $handle Geöffnete Datei
	 *
	 * @return array<int, string|null>|null Die Werte der Zeile, [null] bei
	 *                                      einer Leerzeile, null am Dateiende
	 */
	private function naechsteZeile($handle): ?array
	{
		$werte = fgetcsv($handle, 0, ',', '"', '');

		return false === $werte ? null : $werte;
	}

	/**
	 * Benennt die Werte einer Zeile auf die Tabellenfelder um.
	 *
	 * @param array<int, string|null> $werte Die Werte in Dateireihenfolge
	 * @param array<string, int>      $index Spaltenname => Position
	 *
	 * @return array<string, int|string> Feldname => Wert; fehlende
	 *                                   Zusatzspalten werden als leerer
	 *                                   Text bzw. 0 geliefert
	 */
	private function zuordnen(array $werte, array $index): array
	{
		$aufgabe = array();

		foreach (self::SPALTEN as $spalte => $feld) {
			$aufgabe[$feld] = isset($index[$spalte]) ? trim((string) $werte[$index[$spalte]]) : '';
		}

		foreach (array('wertung', 'wertungAbweichung', 'beliebtheit', 'spiele') as $feld) {
			$aufgabe[$feld] = (int) $aufgabe[$feld];
		}

		return $aufgabe;
	}
}
