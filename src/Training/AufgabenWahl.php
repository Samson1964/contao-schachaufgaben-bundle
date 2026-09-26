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
 * Wählt die nächste Aufgabe: zufällig, passend zur Wertung des Spielers und
 * nie eine, die der Spieler schon einmal gestellt bekommen hat.
 *
 * Systematik: Die Zielwertung ist die Wertung des Spielers plus eine
 * zufällige Streuung von ±STREUUNG Punkten. Aus den zehn Aufgaben, die dem
 * Ziel am nächsten liegen und noch nicht gestellt wurden, wird zufällig eine
 * gezogen. Da die Spielerwertung nach jedem Versuch neu berechnet wird,
 * werden die Aufgaben mit steigender Wertung von selbst schwerer und nach
 * Fehlern leichter. Bei gleicher Wertung von Spieler und Aufgabe liegt die
 * Lösungswahrscheinlichkeit nach Glicko-2 bei etwa 50 %.
 *
 * Bereits gestellte Aufgaben werden bei Mitgliedern in der Datenbank
 * ausgeschlossen (NOT EXISTS über den eindeutigen Schlüssel memberId,aufgabe
 * in tl_schachaufgaben_versuch), bei Gästen über die Merkliste aus der
 * Sitzung. Die Suche läuft über den Index (published, wertung) und bleibt
 * so auch bei Millionen Aufgaben schnell.
 */
class AufgabenWahl
{
	/**
	 * Zufällige Abweichung der Zielwertung von der Spielerwertung.
	 */
	public const STREUUNG = 100;

	/**
	 * Anzahl der Aufgaben, die je Suchrichtung als Kandidaten geholt werden.
	 */
	private const KANDIDATEN = 50;

	/**
	 * Aus so vielen dem Ziel nächstgelegenen Kandidaten wird zufällig gezogen.
	 */
	private const ZIEHUNG = 10;

	private Connection $connection;

	/**
	 * Übernimmt die Datenbankverbindung.
	 *
	 * @param Connection $connection Die Datenbankverbindung von Contao
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Sucht eine veröffentlichte, noch nicht gestellte Aufgabe nahe der
	 * Spielerwertung.
	 *
	 * Zuerst werden die nächstschwereren Aufgaben ab der gewürfelten
	 * Zielwertung geholt, dann die nächstleichteren. Bleibt nichts übrig, wird
	 * mit verdoppelter Streuung neu gewürfelt; nach vier Fehlschlägen wird
	 * irgendeine noch nicht gestellte Aufgabe genommen.
	 *
	 * @param float           $wertung  Wertung des Spielers
	 * @param int|null        $memberId Mitglied, dessen gestellte Aufgaben
	 *                                  ausgeschlossen werden; null bei Gästen
	 * @param array<int, int> $gesehen  IDs der Aufgaben, die ein Gast in
	 *                                  dieser Sitzung schon hatte
	 *
	 * @return array<string, mixed>|null Die Zeile aus tl_schachaufgaben, oder
	 *                                   null wenn der Spieler bereits alle
	 *                                   veröffentlichten Aufgaben hatte
	 */
	public function naechste(float $wertung, ?int $memberId, array $gesehen = array()): ?array
	{
		$gesehen = array_flip(array_map('intval', $gesehen));

		for ($versuch = 0; $versuch < 4; ++$versuch) {
			$streuung = self::STREUUNG * 2 ** $versuch;
			$ziel = (int) round($wertung) + random_int(-$streuung, $streuung);

			$kandidaten = array_merge(
				$this->holen('wertung >= ?', 'wertung, id', $ziel, $memberId),
				$this->holen('wertung < ?', 'wertung DESC, id', $ziel, $memberId)
			);

			$kandidaten = array_values(array_filter(
				$kandidaten,
				static function (array $zeile) use ($gesehen): bool {
					return !isset($gesehen[(int) $zeile['id']]);
				}
			));

			if (array() !== $kandidaten) {
				// Die dem Ziel nächsten Kandidaten zuerst, dann zufällig aus der Spitzengruppe ziehen
				usort(
					$kandidaten,
					static function (array $a, array $b) use ($ziel): int {
						return abs((int) $a['wertung'] - $ziel) <=> abs((int) $b['wertung'] - $ziel);
					}
				);

				return $kandidaten[random_int(0, min(self::ZIEHUNG, \count($kandidaten)) - 1)];
			}
		}

		// Letzter Ausweg: irgendeine noch nicht gestellte Aufgabe
		$parameter = array();
		$sql = "SELECT * FROM tl_schachaufgaben a WHERE a.published='1'".$this->ausschluss($memberId, $parameter);

		if (array() !== $gesehen) {
			$sql .= ' AND a.id NOT IN ('.implode(',', array_keys($gesehen)).')';
		}

		$zeile = $this->connection->fetchAssociative($sql.' ORDER BY RAND() LIMIT 1', $parameter);

		return false === $zeile ? null : $zeile;
	}

	/**
	 * Holt Kandidaten in einer Richtung ab der Zielwertung.
	 *
	 * @param string   $bedingung Bedingung auf die Wertung mit einem Platzhalter
	 * @param string   $sortierung ORDER-BY-Ausdruck
	 * @param int      $ziel      Die Zielwertung
	 * @param int|null $memberId  Mitglied für den Ausschluss, oder null
	 *
	 * @return array<int, array<string, mixed>> Höchstens KANDIDATEN Zeilen
	 */
	private function holen(string $bedingung, string $sortierung, int $ziel, ?int $memberId): array
	{
		$parameter = array($ziel);
		$sql = sprintf("SELECT * FROM tl_schachaufgaben a WHERE a.published='1' AND a.%s", $bedingung)
			.$this->ausschluss($memberId, $parameter)
			.sprintf(' ORDER BY %s LIMIT %d', $sortierung, self::KANDIDATEN);

		return $this->connection->fetchAllAssociative($sql, $parameter);
	}

	/**
	 * Baut die Bedingung, die einem Mitglied bereits gestellte Aufgaben
	 * ausschließt.
	 *
	 * @param int|null          $memberId  Mitglied, oder null für Gäste
	 * @param array<int, mixed> $parameter Wird um die Mitglieds-ID ergänzt
	 *
	 * @return string SQL-Teil, beginnend mit „ AND", oder leer bei Gästen
	 */
	private function ausschluss(?int $memberId, array &$parameter): string
	{
		if (null === $memberId) {
			return '';
		}

		$parameter[] = $memberId;

		return ' AND NOT EXISTS (SELECT 1 FROM tl_schachaufgaben_versuch v WHERE v.memberId=? AND v.aufgabe=a.id)';
	}
}
