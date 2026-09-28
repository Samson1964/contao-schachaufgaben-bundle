<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Training;

use Doctrine\DBAL\Connection;

/**
 * Zählt Aufrufe und gespielte Aufgaben und wertet sie aus.
 *
 * Gezählt wird stundenweise in tl_schachaufgaben_statistik. Die Ranglisten
 * („meistgespielt", „aktivste Mitglieder") kommen dagegen aus
 * tl_schachaufgaben_versuch, weil nur dort steht, wer welche Aufgabe gespielt
 * hat – also nur für Mitglieder.
 */
class Statistik
{
	public const AUFRUF = 'aufruf';

	public const BEGONNEN = 'begonnen';

	public const GELOEST = 'geloest';

	public const NICHT_GELOEST = 'nichtgeloest';

	/**
	 * Übernimmt die Datenbankverbindung.
	 *
	 * @param Connection $connection Die Datenbankverbindung von Contao
	 */
	public function __construct(private readonly Connection $connection)
	{
	}

	/**
	 * Erhöht den Zähler einer Art in der laufenden Stunde um eins.
	 *
	 * Ein Fehler beim Zählen darf das Training nicht stören; er wird deshalb
	 * verschluckt. Die Statistik ist eine Beigabe, keine Voraussetzung.
	 *
	 * @param string $art  Eine der Konstanten AUFRUF, BEGONNEN, GELOEST, NICHT_GELOEST
	 * @param bool   $gast true für Gäste, false für Mitglieder
	 */
	public function zaehlen(string $art, bool $gast): void
	{
		try {
			$this->connection->executeStatement(
				'INSERT INTO tl_schachaufgaben_statistik (datum, stunde, art, gast, anzahl) VALUES (?, ?, ?, ?, 1)
				 ON DUPLICATE KEY UPDATE anzahl = anzahl + 1',
				array((int) date('Ymd'), (int) date('G'), $art, $gast ? '1' : '')
			);
		} catch (\Throwable) {
			// siehe Kommentarblock
		}
	}

	/**
	 * Summiert die Zähler eines Zeitraums nach Art und Spielergruppe.
	 *
	 * @param int $von Erster Tag als JJJJMMTT
	 * @param int $bis Letzter Tag als JJJJMMTT (einschließlich)
	 *
	 * @return array<string, array{mitglieder: int, gaeste: int, gesamt: int}> Je Art
	 */
	public function summen(int $von, int $bis): array
	{
		$summen = array();

		foreach (array(self::AUFRUF, self::BEGONNEN, self::GELOEST, self::NICHT_GELOEST) as $art) {
			$summen[$art] = array('mitglieder' => 0, 'gaeste' => 0, 'gesamt' => 0);
		}

		$zeilen = $this->connection->fetchAllAssociative(
			'SELECT art, gast, SUM(anzahl) AS anzahl FROM tl_schachaufgaben_statistik WHERE datum BETWEEN ? AND ? GROUP BY art, gast',
			array($von, $bis)
		);

		foreach ($zeilen as $zeile) {
			if (!isset($summen[$zeile['art']])) {
				continue;
			}

			$summen[$zeile['art']]['1' === $zeile['gast'] ? 'gaeste' : 'mitglieder'] += (int) $zeile['anzahl'];
			$summen[$zeile['art']]['gesamt'] += (int) $zeile['anzahl'];
		}

		return $summen;
	}

	/**
	 * Liefert den Verlauf einer Art, aufgeteilt nach Stunden, Tagen oder Monaten.
	 *
	 * @param string $art     Eine der Konstanten
	 * @param int    $von     Erster Tag als JJJJMMTT
	 * @param int    $bis     Letzter Tag als JJJJMMTT (einschließlich)
	 * @param string $einheit „stunde", „tag" oder „monat"
	 *
	 * @return array<int, int> Einheit (Stunde 0–23, Tag 1–31 bzw. Monat 1–12) => Summe
	 */
	public function verlauf(string $art, int $von, int $bis, string $einheit): array
	{
		$ausdruck = match ($einheit) {
			'stunde' => 'stunde',
			'tag'    => 'datum % 100',
			default  => 'FLOOR(datum / 100) % 100',
		};

		$zeilen = $this->connection->fetchAllAssociative(
			sprintf('SELECT %s AS einheit, SUM(anzahl) AS anzahl FROM tl_schachaufgaben_statistik WHERE art = ? AND datum BETWEEN ? AND ? GROUP BY einheit', $ausdruck),
			array($art, $von, $bis)
		);

		$verlauf = array();

		foreach ($zeilen as $zeile) {
			$verlauf[(int) $zeile['einheit']] = (int) $zeile['anzahl'];
		}

		return $verlauf;
	}

	/**
	 * Die im Zeitraum am häufigsten von Mitgliedern gespielten Aufgaben.
	 *
	 * @param int $beginn Unix-Zeitstempel, einschließlich
	 * @param int $ende   Unix-Zeitstempel, ausschließlich
	 * @param int $anzahl Höchstzahl der Zeilen
	 *
	 * @return array<int, array<string, mixed>> id, lichessId, quelle, wertung, beliebtheit, gespielt, geloest
	 */
	public function meistgespielt(int $beginn, int $ende, int $anzahl): array
	{
		return $this->connection->fetchAllAssociative(
			sprintf(
				"SELECT a.id, a.lichessId, a.quelle, a.wertung, a.beliebtheit, COUNT(*) AS gespielt, SUM(v.geloest = '1') AS geloest
				 FROM tl_schachaufgaben_versuch v INNER JOIN tl_schachaufgaben a ON a.id = v.aufgabe
				 WHERE v.tstamp >= ? AND v.tstamp < ?
				 GROUP BY a.id ORDER BY gespielt DESC, a.id LIMIT %d",
				max(1, $anzahl)
			),
			array($beginn, $ende)
		);
	}

	/**
	 * Die im Zeitraum aktivsten Mitglieder.
	 *
	 * @param int $beginn Unix-Zeitstempel, einschließlich
	 * @param int $ende   Unix-Zeitstempel, ausschließlich
	 * @param int $anzahl Höchstzahl der Zeilen
	 *
	 * @return array<int, array<string, mixed>> name („Vorname N."), gespielt, geloest, wertung
	 */
	public function aktivsteMitglieder(int $beginn, int $ende, int $anzahl): array
	{
		$zeilen = $this->connection->fetchAllAssociative(
			sprintf(
				"SELECT m.firstname, m.lastname, COUNT(*) AS gespielt, SUM(v.geloest = '1') AS geloest, MAX(s.wertung) AS wertung
				 FROM tl_schachaufgaben_versuch v
				 INNER JOIN tl_member m ON m.id = v.memberId
				 LEFT JOIN tl_schachaufgaben_spieler s ON s.memberId = v.memberId
				 WHERE v.tstamp >= ? AND v.tstamp < ?
				 GROUP BY v.memberId ORDER BY gespielt DESC, v.memberId LIMIT %d",
				max(1, $anzahl)
			),
			array($beginn, $ende)
		);

		return array_map(
			static fn (array $zeile): array => array(
				'name'     => Anzeigename::kurz($zeile['firstname'], $zeile['lastname']),
				'gespielt' => (int) $zeile['gespielt'],
				'geloest'  => (int) $zeile['geloest'],
				'wertung'  => (int) round((float) $zeile['wertung']),
			),
			$zeilen
		);
	}

	/**
	 * Zahlen, die nicht vom Zeitraum abhängen, und neue Mitglieder im Zeitraum.
	 *
	 * @param int $beginn Unix-Zeitstempel, einschließlich
	 * @param int $ende   Unix-Zeitstempel, ausschließlich
	 *
	 * @return array{aufgaben: int, spieler: int, neueSpieler: int}
	 */
	public function bestand(int $beginn, int $ende): array
	{
		return array(
			'aufgaben'    => (int) $this->connection->fetchOne("SELECT COUNT(*) FROM tl_schachaufgaben WHERE published = '1'"),
			'spieler'     => (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_schachaufgaben_spieler WHERE versuche > 0'),
			'neueSpieler' => (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_schachaufgaben_spieler WHERE ersteNutzung >= ? AND ersteNutzung < ?', array($beginn, $ende)),
		);
	}
}
