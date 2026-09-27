<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

/**
 * Löscht Wertung und Versuche eines Mitglieds, wenn das Mitglied gelöscht wird.
 *
 * Mitglieder verschwinden auf zwei Wegen: im Backend (ondelete_callback von
 * tl_member) und über das Frontend-Modul „Konto schließen" (Hook
 * closeAccount). Ohne das Aufräumen blieben verwaiste Zeilen zurück; die
 * Rangliste zeigt sie zwar nicht (INNER JOIN auf tl_member), die Tabellen
 * wüchsen aber unbemerkt weiter.
 *
 * Die Zähler „spiele" und „gefällt/gefällt nicht" der Aufgaben bleiben
 * unverändert: Sie beschreiben, wie oft die Aufgabe gespielt und bewertet
 * wurde, und das bleibt richtig, auch wenn der Spieler geht.
 */
class MitgliedLoeschenListener
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
	 * Räumt beim Löschen eines Mitglieds im Backend auf.
	 *
	 * Contao ruft den Callback vor dem eigentlichen Löschen auf, auch beim
	 * Löschen mehrerer Mitglieder auf einmal (einmal je Mitglied).
	 *
	 * @param DataContainer $dc Der Data Container mit der ID des Mitglieds
	 */
	#[AsCallback(table: 'tl_member', target: 'config.ondelete')]
	public function imBackend(DataContainer $dc): void
	{
		if ($dc->id) {
			$this->loeschen((int) $dc->id);
		}
	}

	/**
	 * Räumt auf, wenn ein Mitglied sein Konto im Frontend schließt.
	 *
	 * Nur beim Löschen des Kontos, nicht beim bloßen Deaktivieren: Ein
	 * deaktiviertes Mitglied kann wieder freigeschaltet werden und soll dann
	 * seine Wertung behalten.
	 *
	 * @param int    $memberId ID des Mitglieds
	 * @param string $modus    „close_delete" oder „close_deactivate"
	 */
	#[AsHook('closeAccount')]
	public function imFrontend(int $memberId, string $modus): void
	{
		if ('close_delete' === $modus) {
			$this->loeschen($memberId);
		}
	}

	/**
	 * Löscht die Zeilen des Mitglieds aus Wertung, Versuchen und Monatsranglisten.
	 *
	 * @param int $memberId ID des Mitglieds aus tl_member
	 */
	private function loeschen(int $memberId): void
	{
		$this->connection->executeStatement('DELETE FROM tl_schachaufgaben_versuch WHERE memberId=?', array($memberId));
		$this->connection->executeStatement('DELETE FROM tl_schachaufgaben_spieler WHERE memberId=?', array($memberId));
		$this->connection->executeStatement('DELETE FROM tl_schachaufgaben_ranglistenstand WHERE memberId=?', array($memberId));
	}
}
