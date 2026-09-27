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
 * Speichert die Rangliste zu einem Stichtag (Monatserster) in
 * tl_schachaufgaben_ranglistenstand.
 *
 * Aufgenommen werden alle nicht gesperrten Mitglieder mit mindestens einer
 * gespielten Aufgabe, sortiert nach aktueller Wertung. Welche davon eine
 * Anzeige später zeigt (etwa ab einer Mindestzahl von Aufgaben), bleibt der
 * Anzeige überlassen; gespeichert wird vollständig, damit nichts verloren geht.
 */
class Ranglistenstand
{
	/**
	 * Übernimmt die Datenbankverbindung.
	 *
	 * @param Connection $connection Die Datenbankverbindung von Contao
	 */
	public function __construct(private readonly Connection $connection)
	{
	}

	/**
	 * Berechnet den Stichtag zu einem Zeitpunkt: den Ersten des Monats, 0 Uhr.
	 *
	 * Maßgeblich ist die Zeitzone des Servers, wie überall in Contao.
	 *
	 * @param int $zeitpunkt Unix-Zeitstempel
	 *
	 * @return int Unix-Zeitstempel des Monatsersten, 0 Uhr
	 */
	public static function stichtag(int $zeitpunkt): int
	{
		return (int) mktime(0, 0, 0, (int) date('n', $zeitpunkt), 1, (int) date('Y', $zeitpunkt));
	}

	/**
	 * Prüft, ob für einen Stichtag schon ein Stand gespeichert ist.
	 *
	 * @param int $stichtag Monatserster aus stichtag()
	 *
	 * @return bool true, wenn mindestens eine Zeile zu diesem Stichtag existiert
	 */
	public function vorhanden(int $stichtag): bool
	{
		return false !== $this->connection->fetchOne('SELECT 1 FROM tl_schachaufgaben_ranglistenstand WHERE stichtag=? LIMIT 1', array($stichtag));
	}

	/**
	 * Speichert die aktuelle Rangliste unter dem angegebenen Stichtag.
	 *
	 * Existiert der Stand schon, geschieht nichts; der eindeutige Schlüssel
	 * (stichtag, memberId) sichert das zusätzlich gegen zwei gleichzeitige
	 * Cronläufe ab. Die Plätze folgen der Wertung, bei Gleichstand entscheidet
	 * die Zahl der gespielten Aufgaben.
	 *
	 * @param int $stichtag Monatserster aus stichtag()
	 *
	 * @return int Anzahl der gespeicherten Mitglieder; 0 wenn der Stand schon
	 *             existierte oder noch niemand gespielt hat
	 */
	public function speichern(int $stichtag): int
	{
		if ($this->vorhanden($stichtag)) {
			return 0;
		}

		$zeilen = $this->connection->fetchAllAssociative(
			"SELECT s.memberId, s.wertung, s.wertungAbweichung, s.versuche, s.geloest, s.bestWertung, m.firstname, m.lastname
			 FROM tl_schachaufgaben_spieler s
			 INNER JOIN tl_member m ON m.id = s.memberId
			 WHERE s.versuche > 0 AND m.disable = ''
			 ORDER BY s.wertung DESC, s.versuche DESC, s.memberId"
		);

		$jetzt = time();
		$gespeichert = 0;
		$this->connection->beginTransaction();

		try {
			foreach ($zeilen as $index => $zeile) {
				$gespeichert += (int) $this->connection->executeStatement(
					'INSERT IGNORE INTO tl_schachaufgaben_ranglistenstand
					 (tstamp, stichtag, memberId, platz, name, wertung, wertungAbweichung, versuche, geloest, bestWertung)
					 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
					array(
						$jetzt,
						$stichtag,
						(int) $zeile['memberId'],
						$index + 1,
						Anzeigename::kurz($zeile['firstname'], $zeile['lastname']),
						max(0, (int) round((float) $zeile['wertung'])),
						max(0, (int) round((float) $zeile['wertungAbweichung'])),
						(int) $zeile['versuche'],
						(int) $zeile['geloest'],
						max(0, (int) round((float) $zeile['bestWertung'])),
					)
				);
			}

			$this->connection->commit();
		} catch (\Throwable $e) {
			$this->connection->rollBack();

			throw $e;
		}

		return $gespeichert;
	}
}
