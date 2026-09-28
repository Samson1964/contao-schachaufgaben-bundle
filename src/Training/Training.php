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
use Schachbulle\ContaoSchachaufgabenBundle\Wertung\Glicko2;
use Schachbulle\ContaoSchachaufgabenBundle\Wertung\Wertung;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Ablauf des Trainings: Aufgabe stellen, Ergebnis werten, Wertung speichern.
 *
 * Mitglieder werden über ihre ID aus tl_member geführt; Wertung und gestellte
 * Aufgaben stehen in der Datenbank. Gäste haben nur die Sitzung: Wertung,
 * Merkliste der gestellten Aufgaben und die gerade offene Aufgabe.
 *
 * Gewertet wird nur die Aufgabe, die zuvor gestellt und noch nicht gewertet
 * wurde. Das verhindert, dass dieselbe Aufgabe mehrfach als gelöst gemeldet
 * wird. Ob sie wirklich gelöst wurde, meldet der Browser — das lässt sich wie
 * bei Lichess nicht fälschungssicher prüfen, ohne die Lösung zurückzuhalten.
 */
class Training
{
	private const SITZUNG_GAST = 'schachaufgaben_gast';

	private const SITZUNG_GESEHEN = 'schachaufgaben_gesehen';

	private const SITZUNG_OFFEN = 'schachaufgaben_offen';

	private const SITZUNG_STIMMEN = 'schachaufgaben_stimmen';

	/**
	 * So viele gestellte, noch nicht gewertete Aufgaben merkt sich die Sitzung.
	 * Mehr als eine, damit sich zwei Browser-Tabs nicht gegenseitig die offene
	 * Aufgabe wegnehmen.
	 */
	private const MAX_OFFEN = 10;

	/**
	 * Ab dieser Abweichung (RD) gilt eine Wertung als gesichert. Erst dann
	 * zählt sie als Bestwertung. Lichess nennt Wertungen mit größerer
	 * Abweichung „vorläufig"; bei neuen Spielern (Start 350) ist das nach etwa
	 * 12 bis 15 Aufgaben erreicht. Ohne diese Grenze wäre die Bestwertung oft
	 * nur der Ausschlag nach der ersten gelösten Aufgabe (1500 → gut 1700).
	 */
	public const GESICHERTE_ABWEICHUNG = 110;

	/**
	 * Obergrenze der Merkliste eines Gastes, damit die Sitzung nicht
	 * unbegrenzt wächst. Die ältesten Einträge fallen zuerst heraus.
	 */
	private const MAX_GESEHEN = 5000;

	private Connection $connection;

	private AufgabenWahl $wahl;

	private Glicko2 $glicko;

	/**
	 * Übernimmt die benötigten Dienste.
	 *
	 * @param Connection   $connection Die Datenbankverbindung von Contao
	 * @param AufgabenWahl $wahl       Sucht die nächste passende Aufgabe
	 * @param Glicko2      $glicko     Berechnet die Wertungen
	 */
	public function __construct(Connection $connection, AufgabenWahl $wahl, Glicko2 $glicko)
	{
		$this->connection = $connection;
		$this->wahl = $wahl;
		$this->glicko = $glicko;
	}

	/**
	 * Stellt die nächste Aufgabe und merkt sie sich in der Sitzung als offen.
	 *
	 * Gespeichert wird dabei noch nichts: Erst der erste Zug (beginnen()) legt
	 * die Einträge in tl_schachaufgaben_spieler und tl_schachaufgaben_versuch
	 * bzw. in der Merkliste eines Gastes an. Eine nur angesehene und dann
	 * übersprungene Aufgabe kann deshalb später wieder gestellt werden.
	 *
	 * @param int|null         $memberId ID des angemeldeten Mitglieds, null für Gäste
	 * @param SessionInterface $session  Die Sitzung (vermerkt die offene Aufgabe)
	 *
	 * @return array<string, mixed>|null Daten für den Browser: id, fen, zuege
	 *                                   und der Stand des Spielers; null, wenn
	 *                                   es keine ungespielte Aufgabe mehr gibt
	 */
	public function aufgabeStellen(?int $memberId, SessionInterface $session): ?array
	{
		$spieler = $this->spielerLaden($memberId, $session);
		$gesehen = null === $memberId ? (array) $session->get(self::SITZUNG_GESEHEN, array()) : array();

		$aufgabe = $this->wahl->naechste($spieler['wertung']->getWertung(), $memberId, $gesehen);

		if (null === $aufgabe) {
			return null;
		}

		$id = (int) $aufgabe['id'];
		$offen = $this->offen($session);
		$offen[] = $id;
		$session->set(self::SITZUNG_OFFEN, \array_slice(array_values(array_unique($offen)), -self::MAX_OFFEN));

		return array(
			'id'      => $id,
			'fen'     => (string) $aufgabe['fen'],
			'zuege'   => preg_split('/\s+/', trim((string) $aufgabe['zuege']), -1, PREG_SPLIT_NO_EMPTY),
			// true: kein auslösender Gegnerzug, der Spieler ist sofort am Zug
			'spielerZuerst' => '1' === (string) ($aufgabe['spielerZuerst'] ?? ''),
			'spieler' => $this->spielerDaten($spieler, null === $memberId),
		);
	}

	/**
	 * Vermerkt den Beginn einer gestellten Aufgabe: der Spieler hat den ersten
	 * Zug gemacht (oder sich die Lösung zeigen lassen).
	 *
	 * Erst jetzt entstehen die Einträge: bei Mitgliedern die Zeile in
	 * tl_schachaufgaben_spieler (samt erster Nutzung, falls es die allererste
	 * Aufgabe ist) und der Eintrag in tl_schachaufgaben_versuch, bei Gästen der
	 * Eintrag in der Merkliste. Damit wird die Aufgabe nie ein zweites Mal
	 * gestellt. Ein wiederholter Aufruf ändert nichts.
	 *
	 * @param int|null         $memberId  ID des Mitglieds, oder null für Gäste
	 * @param SessionInterface $session   Die Sitzung
	 * @param int              $aufgabeId ID der Aufgabe
	 *
	 * @return bool true, wenn die Aufgabe begonnen ist (jetzt oder schon
	 *              vorher); false, wenn sie diesem Spieler nicht gestellt wurde
	 */
	public function beginnen(?int $memberId, SessionInterface $session, int $aufgabeId): bool
	{
		$offen = \in_array($aufgabeId, $this->offen($session), true);

		if (null !== $memberId) {
			if (false !== $this->connection->fetchOne('SELECT 1 FROM tl_schachaufgaben_versuch WHERE memberId=? AND aufgabe=?', array($memberId, $aufgabeId))) {
				return true;
			}

			if (!$offen) {
				return false;
			}

			$spieler = $this->spielerLaden($memberId, $session);

			// Bei der allerersten Aufgabe entsteht hier die Zeile des Mitglieds mit
			// dem Zeitpunkt der ersten Nutzung; die Wertung hat noch die Startwerte
			$this->connection->executeStatement(
				'INSERT IGNORE INTO tl_schachaufgaben_spieler (tstamp, memberId, ersteNutzung) VALUES (?, ?, ?)',
				array(time(), $memberId, time())
			);

			$this->connection->executeStatement(
				'INSERT IGNORE INTO tl_schachaufgaben_versuch (tstamp, memberId, aufgabe, wertungVorher) VALUES (?, ?, ?, ?)',
				array(time(), $memberId, $aufgabeId, $this->runden($spieler['wertung']->getWertung()))
			);

			return true;
		}

		$gesehen = (array) $session->get(self::SITZUNG_GESEHEN, array());

		if (\in_array($aufgabeId, $gesehen, true)) {
			return true;
		}

		if (!$offen) {
			return false;
		}

		$gesehen[] = $aufgabeId;
		$session->set(self::SITZUNG_GESEHEN, \array_slice($gesehen, -self::MAX_GESEHEN));

		return true;
	}

	/**
	 * Wertet das Ergebnis einer gestellten Aufgabe.
	 *
	 * Die Spielerwertung wird immer neu berechnet. Die Wertung der Aufgabe
	 * ändert sich nur durch Mitglieder, damit anonyme Gäste sie nicht
	 * verfälschen können; der Zähler „spiele" zählt alle Versuche.
	 *
	 * @param int|null         $memberId ID des angemeldeten Mitglieds, null für Gäste
	 * @param SessionInterface $session  Die Sitzung
	 * @param int              $aufgabeId ID der Aufgabe aus tl_schachaufgaben
	 * @param bool             $geloest  Ob die Aufgabe ohne Fehler gelöst wurde
	 *
	 * @return array<string, mixed>|null Alte und neue Wertung sowie die
	 *                                   Angaben zur Aufgabe; null, wenn die
	 *                                   Aufgabe nicht gestellt oder schon
	 *                                   gewertet wurde
	 */
	public function ergebnisWerten(?int $memberId, SessionInterface $session, int $aufgabeId, bool $geloest): ?array
	{
		$aufgabe = $this->connection->fetchAssociative('SELECT * FROM tl_schachaufgaben WHERE id=?', array($aufgabeId));

		// „Lösung zeigen" ohne vorherigen Zug: der Beginn wird hier nachgeholt
		if (false === $aufgabe || !$this->beginnen($memberId, $session, $aufgabeId) || !$this->offeneAufgabeAbschliessen($memberId, $session, $aufgabeId)) {
			return null;
		}

		$spieler = $this->spielerLaden($memberId, $session);
		$vorher = $spieler['wertung'];
		$aufgabeWertung = new Wertung((float) $aufgabe['wertung'], (float) $aufgabe['wertungAbweichung'], (float) $aufgabe['wertungVolatilitaet']);

		$spieler['wertung'] = $this->glicko->versuch($vorher, $aufgabeWertung, $geloest ? 1.0 : 0.0);
		++$spieler['versuche'];
		$spieler['geloest'] += $geloest ? 1 : 0;

		if (null !== $memberId) {
			// Die Aufgabe „spielt" mit dem umgekehrten Ergebnis gegen die alte Spielerwertung
			$aufgabeNeu = $this->glicko->versuch($aufgabeWertung, $vorher, $geloest ? 0.0 : 1.0);

			$this->connection->executeStatement(
				'UPDATE tl_schachaufgaben SET wertung=?, wertungAbweichung=?, wertungVolatilitaet=?, spiele=spiele+1 WHERE id=?',
				array($this->runden($aufgabeNeu->getWertung()), $this->runden($aufgabeNeu->getAbweichung()), $aufgabeNeu->getVolatilitaet(), $aufgabeId)
			);

			$this->connection->executeStatement(
				"UPDATE tl_schachaufgaben_versuch SET geloest=?, wertungVorher=?, wertungNachher=? WHERE memberId=? AND aufgabe=?",
				array($geloest ? '1' : '', $this->runden($vorher->getWertung()), $this->runden($spieler['wertung']->getWertung()), $memberId, $aufgabeId)
			);
		} else {
			$this->connection->executeStatement('UPDATE tl_schachaufgaben SET spiele=spiele+1 WHERE id=?', array($aufgabeId));
		}

		$this->spielerSpeichern($memberId, $session, $spieler);

		$alt = $this->runden($vorher->getWertung());
		$neu = $this->runden($spieler['wertung']->getWertung());

		return array(
			'geloest'        => $geloest,
			'wertungVorher'  => $alt,
			'wertungNachher' => $neu,
			'differenz'      => $neu - $alt,
			'spieler'        => $this->spielerDaten($spieler, null === $memberId),
			'aufgabe'        => array(
				'wertung'    => (int) $aufgabe['wertung'],
				'motive'     => preg_split('/\s+/', trim((string) $aufgabe['motive']), -1, PREG_SPLIT_NO_EMPTY),
				// Die Übersetzung übernimmt eroeffnung.js mit dem Wörterbuch aus der Sprachdatei
				'eroeffnung' => (string) $aufgabe['eroeffnung'],
				'partieUrl'  => (string) $aufgabe['partieUrl'],
				'lichessId'  => (string) $aufgabe['lichessId'],
			),
		);
	}

	/**
	 * Nimmt eine Bewertung „Gefällt mir" / „Gefällt mir nicht" entgegen.
	 *
	 * Jeder Spieler hat je Aufgabe eine Stimme, die er ändern oder mit 0
	 * zurücknehmen kann. Die Zähler der Aufgabe werden um die Differenz zur
	 * bisherigen Stimme verschoben und die Beliebtheit im selben UPDATE neu
	 * berechnet; MySQL und MariaDB werten die Zuweisungen von links nach
	 * rechts aus, die Formel sieht also schon die neuen Zähler.
	 *
	 * Abstimmen darf nur, wer die Aufgabe abgeschlossen hat: Mitglieder brauchen
	 * einen gewerteten Eintrag in tl_schachaufgaben_versuch, Gäste die Aufgabe
	 * in ihrer Merkliste, ohne dass sie noch offen ist.
	 *
	 * @param int|null         $memberId  ID des Mitglieds, oder null für Gäste
	 * @param SessionInterface $session   Die Sitzung
	 * @param int              $aufgabeId ID der Aufgabe
	 * @param int              $stimme    1 gefällt, -1 gefällt nicht, 0 zurücknehmen
	 *
	 * @return array<string, int>|null Stimme und neue Zähler der Aufgabe, oder
	 *                                 null wenn der Spieler (noch) nicht
	 *                                 abstimmen darf
	 */
	public function bewerten(?int $memberId, SessionInterface $session, int $aufgabeId, int $stimme): ?array
	{
		$stimme = max(-1, min(1, $stimme));

		if (null !== $memberId) {
			$alt = $this->connection->fetchOne(
				"SELECT stimme FROM tl_schachaufgaben_versuch WHERE memberId=? AND aufgabe=? AND gewertet='1'",
				array($memberId, $aufgabeId)
			);

			if (false === $alt) {
				return null;
			}

			$alt = (int) $alt;
		} else {
			$gesehen = (array) $session->get(self::SITZUNG_GESEHEN, array());

			if (!\in_array($aufgabeId, $gesehen, true) || \in_array($aufgabeId, $this->offen($session), true)) {
				return null;
			}

			$stimmen = (array) $session->get(self::SITZUNG_STIMMEN, array());
			$alt = (int) ($stimmen[$aufgabeId] ?? 0);
		}

		if ($alt !== $stimme) {
			$this->connection->executeStatement(
				'UPDATE tl_schachaufgaben
				 SET gefaellt = gefaellt + ?, gefaelltNicht = gefaelltNicht + ?,
				     beliebtheit = IF(gefaellt + gefaelltNicht = 0, 0, ROUND(100 * (CAST(gefaellt AS SIGNED) - CAST(gefaelltNicht AS SIGNED)) / (gefaellt + gefaelltNicht)))
				 WHERE id=?',
				array((1 === $stimme ? 1 : 0) - (1 === $alt ? 1 : 0), (-1 === $stimme ? 1 : 0) - (-1 === $alt ? 1 : 0), $aufgabeId)
			);

			if (null !== $memberId) {
				$this->connection->executeStatement(
					'UPDATE tl_schachaufgaben_versuch SET stimme=? WHERE memberId=? AND aufgabe=?',
					array($stimme, $memberId, $aufgabeId)
				);
			} else {
				$stimmen[$aufgabeId] = $stimme;
				$session->set(self::SITZUNG_STIMMEN, \array_slice($stimmen, -self::MAX_GESEHEN, null, true));
			}
		}

		$zeile = $this->connection->fetchAssociative('SELECT gefaellt, gefaelltNicht, beliebtheit FROM tl_schachaufgaben WHERE id=?', array($aufgabeId));

		return array(
			'stimme'        => $stimme,
			'gefaellt'      => (int) ($zeile['gefaellt'] ?? 0),
			'gefaelltNicht' => (int) ($zeile['gefaelltNicht'] ?? 0),
			'beliebtheit'   => (int) ($zeile['beliebtheit'] ?? 0),
		);
	}

	/**
	 * Liefert den Stand des Spielers ohne eine Aufgabe zu stellen.
	 *
	 * @param int|null         $memberId ID des angemeldeten Mitglieds, null für Gäste
	 * @param SessionInterface $session  Die Sitzung
	 *
	 * @return array<string, mixed> wertung, versuche, geloest und gast
	 */
	public function spielerStand(?int $memberId, SessionInterface $session): array
	{
		return $this->spielerDaten($this->spielerLaden($memberId, $session), null === $memberId);
	}

	/**
	 * Markiert die gestellte Aufgabe als gewertet — genau einmal.
	 *
	 * Bei Mitgliedern geschieht das atomar per UPDATE mit Bedingung
	 * gewertet='', sodass zwei gleichzeitige Meldungen (etwa aus zwei Tabs)
	 * nicht doppelt zählen. Bei Gästen muss die Aufgabe unter den in der
	 * Sitzung vermerkten offenen Aufgaben sein. In beiden Fällen ist sie danach
	 * nicht mehr offen.
	 *
	 * @param int|null         $memberId  ID des Mitglieds, oder null
	 * @param SessionInterface $session   Die Sitzung
	 * @param int              $aufgabeId Die gemeldete Aufgabe
	 *
	 * @return bool true, wenn die Aufgabe offen war und jetzt gewertet werden darf
	 */
	private function offeneAufgabeAbschliessen(?int $memberId, SessionInterface $session, int $aufgabeId): bool
	{
		$offen = $this->offen($session);

		if (null !== $memberId) {
			$frei = 1 === (int) $this->connection->executeStatement(
				"UPDATE tl_schachaufgaben_versuch SET gewertet='1' WHERE memberId=? AND aufgabe=? AND gewertet=''",
				array($memberId, $aufgabeId)
			);
		} else {
			$frei = \in_array($aufgabeId, $offen, true);
		}

		if ($frei) {
			$session->set(self::SITZUNG_OFFEN, array_values(array_diff($offen, array($aufgabeId))));
		}

		return $frei;
	}

	/**
	 * Liefert die in der Sitzung vermerkten offenen Aufgaben.
	 *
	 * Bis 1.2.0 stand dort bei Gästen eine einzelne ID statt einer Liste; auch
	 * diese Form wird noch gelesen, damit laufende Sitzungen das Update überstehen.
	 *
	 * @param SessionInterface $session Die Sitzung
	 *
	 * @return array<int, int> IDs der gestellten, noch nicht gewerteten Aufgaben
	 */
	private function offen(SessionInterface $session): array
	{
		$wert = $session->get(self::SITZUNG_OFFEN, array());

		return array_map('intval', \is_array($wert) ? $wert : array($wert));
	}

	/**
	 * Lädt Wertung und Zähler des Spielers.
	 *
	 * @param int|null         $memberId ID des Mitglieds, oder null für Gäste
	 * @param SessionInterface $session  Die Sitzung
	 *
	 * @return array{wertung: Wertung, versuche: int, geloest: int} Startwerte
	 *                                                              1500/350/0,06,
	 *                                                              wenn noch nichts
	 *                                                              gespeichert ist
	 */
	private function spielerLaden(?int $memberId, SessionInterface $session): array
	{
		if (null === $memberId) {
			$zeile = $session->get(self::SITZUNG_GAST);
		} else {
			$zeile = $this->connection->fetchAssociative('SELECT * FROM tl_schachaufgaben_spieler WHERE memberId=?', array($memberId));
		}

		if (!\is_array($zeile)) {
			return array('wertung' => new Wertung(), 'versuche' => 0, 'geloest' => 0);
		}

		return array(
			'wertung'  => new Wertung((float) $zeile['wertung'], (float) $zeile['wertungAbweichung'], (float) $zeile['wertungVolatilitaet']),
			'versuche' => (int) $zeile['versuche'],
			'geloest'  => (int) $zeile['geloest'],
		);
	}

	/**
	 * Speichert Wertung und Zähler des Spielers.
	 *
	 * Bei Mitgliedern wird dabei die Bestwertung mitgeführt: Ist die neue
	 * Wertung gesichert und höher als die bisher beste, wird sie mit Datum
	 * übernommen. Die erste Nutzung setzt schon aufgabeStellen(); hier wird sie
	 * nur nachgetragen, falls sie fehlt.
	 *
	 * @param int|null                                              $memberId ID des Mitglieds, oder null
	 * @param SessionInterface                                      $session  Die Sitzung
	 * @param array{wertung: Wertung, versuche: int, geloest: int} $spieler  Der neue Stand
	 */
	private function spielerSpeichern(?int $memberId, SessionInterface $session, array $spieler): void
	{
		$werte = array(
			'wertung'             => $spieler['wertung']->getWertung(),
			'wertungAbweichung'   => $spieler['wertung']->getAbweichung(),
			'wertungVolatilitaet' => $spieler['wertung']->getVolatilitaet(),
			'versuche'            => $spieler['versuche'],
			'geloest'             => $spieler['geloest'],
		);

		if (null === $memberId) {
			$session->set(self::SITZUNG_GAST, $werte);

			return;
		}

		// Als Bestwertung zählt nur eine gesicherte Wertung (siehe GESICHERTE_ABWEICHUNG)
		$jetzt = time();
		$kandidat = $werte['wertungAbweichung'] <= self::GESICHERTE_ABWEICHUNG ? $werte['wertung'] : 0.0;

		// bestDatum steht vor bestWertung: MySQL und MariaDB werten die Zuweisungen
		// von links nach rechts aus, der Vergleich muss die alte Bestwertung sehen
		$this->connection->executeStatement(
			'INSERT INTO tl_schachaufgaben_spieler (tstamp, memberId, wertung, wertungAbweichung, wertungVolatilitaet, versuche, geloest, bestWertung, bestDatum, ersteNutzung)
			 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			 ON DUPLICATE KEY UPDATE tstamp=VALUES(tstamp), wertung=VALUES(wertung), wertungAbweichung=VALUES(wertungAbweichung),
			 wertungVolatilitaet=VALUES(wertungVolatilitaet), versuche=VALUES(versuche), geloest=VALUES(geloest),
			 bestDatum=IF(VALUES(bestWertung) > bestWertung, VALUES(bestDatum), bestDatum),
			 bestWertung=GREATEST(bestWertung, VALUES(bestWertung)),
			 ersteNutzung=IF(ersteNutzung = 0, VALUES(ersteNutzung), ersteNutzung)',
			array($jetzt, $memberId, $werte['wertung'], $werte['wertungAbweichung'], $werte['wertungVolatilitaet'], $werte['versuche'], $werte['geloest'], $kandidat, $kandidat > 0 ? $jetzt : 0, $jetzt)
		);
	}

	/**
	 * Bereitet den Stand des Spielers für den Browser auf.
	 *
	 * @param array{wertung: Wertung, versuche: int, geloest: int} $spieler Der Stand
	 * @param bool                                                  $gast    Ob es ein Gast ist
	 *
	 * @return array<string, mixed> Gerundete Wertung, Zähler und Gast-Kennzeichen
	 */
	private function spielerDaten(array $spieler, bool $gast): array
	{
		return array(
			'wertung'  => $this->runden($spieler['wertung']->getWertung()),
			'versuche' => $spieler['versuche'],
			'geloest'  => $spieler['geloest'],
			'gast'     => $gast,
		);
	}

	/**
	 * Rundet eine Wertung für Anzeige und smallint-Spalten.
	 *
	 * @param float $wert Ungerundeter Wert
	 *
	 * @return int Kaufmännisch gerundet, nie negativ
	 */
	private function runden(float $wert): int
	{
		return max(0, (int) round($wert));
	}
}
