<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Import;

use Doctrine\DBAL\Connection;

/**
 * Schreibt importierte Aufgaben blockweise in tl_schachaufgaben.
 *
 * Einzelne INSERTs wären bei hunderttausend Aufgaben zu langsam. Der
 * Schreiber sammelt deshalb Zeilen und schickt sie als ein INSERT mit vielen
 * Wertetupeln ab. Nach dem letzten Datensatz muss abschliessen() aufgerufen
 * werden, sonst bleibt der letzte Block liegen.
 */
class AufgabenSchreiber
{
	/**
	 * Felder in der Reihenfolge der Platzhalter im INSERT.
	 */
	private const FELDER = array(
		'tstamp', 'quelle', 'published', 'lichessId', 'fen', 'zuege', 'motive',
		'wertung', 'wertungAbweichung', 'beliebtheit', 'spiele', 'partieUrl', 'eroeffnung',
	);

	/**
	 * Felder, die beim Aktualisieren von Lichess übernommen werden. Die
	 * Wertungsfelder gehören nicht dazu, sobald das Bundle selbst wertet —
	 * dafür gibt es die Option $wertungUebernehmen.
	 */
	private const AKTUALISIEREN = array('fen', 'zuege', 'motive', 'beliebtheit', 'spiele', 'partieUrl', 'eroeffnung');

	private const WERTUNGSFELDER = array('wertung', 'wertungAbweichung');

	private Connection $connection;

	private int $blockgroesse = 500;

	private bool $aktualisieren = false;

	private bool $wertungUebernehmen = false;

	private bool $veroeffentlichen = true;

	/**
	 * @var array<int, array<string, int|string>>
	 */
	private array $block = array();

	private int $geschrieben = 0;

	/**
	 * Übernimmt die Datenbankverbindung; die Einstellungen stehen auf den
	 * Standardwerten, bis einstellen() aufgerufen wird.
	 *
	 * @param Connection $connection Die Datenbankverbindung von Contao
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Legt fest, wie mit bereits vorhandenen Aufgaben verfahren wird.
	 *
	 * Standard ist, vorhandene Aufgaben unberührt zu lassen (INSERT IGNORE).
	 * Mit Aktualisierung werden Stellung, Züge, Motive und die Lichess-Zähler
	 * überschrieben; die Wertung nur auf ausdrücklichen Wunsch, weil sie sonst
	 * die im eigenen Training entstandene Wertung zunichtemachen würde.
	 *
	 * @param bool $aktualisieren      Vorhandene Aufgaben überschreiben
	 * @param bool $wertungUebernehmen Dabei auch Wertung und Abweichung
	 *                                 von Lichess übernehmen
	 * @param bool $veroeffentlichen   Neue Aufgaben sofort veröffentlichen
	 * @param int  $blockgroesse       Zeilen je INSERT, mindestens 1
	 */
	public function einstellen(bool $aktualisieren, bool $wertungUebernehmen, bool $veroeffentlichen, int $blockgroesse = 500): void
	{
		$this->aktualisieren = $aktualisieren;
		$this->wertungUebernehmen = $wertungUebernehmen;
		$this->veroeffentlichen = $veroeffentlichen;
		$this->blockgroesse = max(1, $blockgroesse);
	}

	/**
	 * Nimmt eine Aufgabe auf und schreibt den Block, sobald er voll ist.
	 *
	 * @param array<string, int|string> $aufgabe Zeile aus dem LichessCsvLeser
	 */
	public function hinzufuegen(array $aufgabe): void
	{
		$this->block[] = $aufgabe;

		if (\count($this->block) >= $this->blockgroesse) {
			$this->blockSchreiben();
		}
	}

	/**
	 * Schreibt die restlichen gesammelten Aufgaben.
	 *
	 * @return int Anzahl der von MySQL gemeldeten betroffenen Zeilen über den
	 *             gesamten Import. Bei INSERT IGNORE sind das die neuen
	 *             Aufgaben; bei ON DUPLICATE KEY UPDATE zählt MySQL jede
	 *             geänderte Zeile doppelt, unveränderte gar nicht.
	 */
	public function abschliessen(): int
	{
		$this->blockSchreiben();

		return $this->geschrieben;
	}

	/**
	 * Schickt den gesammelten Block als ein INSERT an die Datenbank.
	 *
	 * Jeder Block läuft in einer eigenen Transaktion: Bricht der Import ab,
	 * bleiben die bis dahin geschriebenen Blöcke erhalten, und ein erneuter
	 * Aufruf überspringt sie dank des eindeutigen Schlüssels auf lichessId.
	 */
	private function blockSchreiben(): void
	{
		if (array() === $this->block) {
			return;
		}

		$tupel = '('.implode(',', array_fill(0, \count(self::FELDER), '?')).')';
		$sql = sprintf(
			'INSERT %sINTO tl_schachaufgaben (%s) VALUES %s',
			$this->aktualisieren ? '' : 'IGNORE ',
			implode(', ', self::FELDER),
			implode(', ', array_fill(0, \count($this->block), $tupel))
		);

		if ($this->aktualisieren) {
			$felder = self::AKTUALISIEREN;

			if ($this->wertungUebernehmen) {
				$felder = array_merge($felder, self::WERTUNGSFELDER);
			}

			$sql .= ' ON DUPLICATE KEY UPDATE '.implode(', ', array_map(
				static function (string $feld): string {
					return sprintf('%1$s=VALUES(%1$s)', $feld);
				},
				$felder
			));
		}

		$werte = array();
		$jetzt = time();

		foreach ($this->block as $aufgabe) {
			$aufgabe['tstamp'] = $jetzt;
			$aufgabe['quelle'] = 'lichess';
			$aufgabe['published'] = $this->veroeffentlichen ? '1' : '';

			foreach (self::FELDER as $feld) {
				$werte[] = $aufgabe[$feld];
			}
		}

		$this->connection->beginTransaction();

		try {
			$this->geschrieben += (int) $this->connection->executeStatement($sql, $werte);
			$this->connection->commit();
		} catch (\Throwable $e) {
			$this->connection->rollBack();

			throw $e;
		}

		$this->block = array();
	}
}
