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
use Schachbulle\ContaoSchachaufgabenBundle\Import\AufgabenPruefer;

/**
 * Prüft Stellung und Züge, wenn eine Aufgabe im Backend gespeichert wird.
 *
 * Es gelten dieselben Regeln wie beim Import (AufgabenPruefer). Eine Ausnahme
 * im save_callback zeigt Contao als Fehlermeldung am Feld an und speichert
 * den Wert nicht.
 */
class AufgabePruefenListener
{
	/**
	 * Übernimmt den Prüfer.
	 *
	 * @param AufgabenPruefer $pruefer Prüft FEN und UCI-Zugfolge
	 */
	public function __construct(private readonly AufgabenPruefer $pruefer)
	{
	}

	/**
	 * Prüft die FEN.
	 *
	 * @param mixed $wert Eingabe aus dem Feld „fen"
	 *
	 * @throws \RuntimeException Mit deutscher Fehlerbeschreibung, wenn die FEN ungültig ist
	 *
	 * @return string Die FEN ohne Leerraum am Rand
	 */
	#[AsCallback(table: 'tl_schachaufgaben', target: 'fields.fen.save')]
	public function fen(mixed $wert): string
	{
		$fen = trim((string) $wert);
		$fehler = $this->pruefer->pruefeFen($fen);

		if (null !== $fehler) {
			throw new \RuntimeException($fehler);
		}

		return $fen;
	}

	/**
	 * Prüft die Zugfolge und vereinheitlicht die Leerzeichen.
	 *
	 * @param mixed $wert Eingabe aus dem Feld „zuege"
	 *
	 * @throws \RuntimeException Mit deutscher Fehlerbeschreibung, wenn die Züge ungültig sind
	 *
	 * @return string Die Züge, durch genau ein Leerzeichen getrennt
	 */
	#[AsCallback(table: 'tl_schachaufgaben', target: 'fields.zuege.save')]
	public function zuege(mixed $wert): string
	{
		$zuege = implode(' ', preg_split('/\s+/', trim((string) $wert), -1, PREG_SPLIT_NO_EMPTY) ?: array());
		$fehler = $this->pruefer->pruefeZuege($zuege);

		if (null !== $fehler) {
			throw new \RuntimeException($fehler);
		}

		return $zuege;
	}
}
