<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Module;

/**
 * Frontend-Modul „Schachaufgaben-Bestenliste": die ewige Bestenliste mit der
 * höchsten je erreichten Wertung jedes Mitglieds und dem Datum dazu.
 *
 * Aufgenommen wird, wer schon einmal eine gesicherte Wertung hatte
 * (bestWertung > 0, siehe Training::GESICHERTE_ABWEICHUNG); eine
 * Mindestzahl von Aufgaben braucht es dafür nicht. Alles Übrige, auch der
 * eigene Platz unter der Tabelle, kommt aus RanglisteModule.
 */
class BestenlisteModule extends RanglisteModule
{
	/**
	 * @var string
	 */
	protected $strTemplate = 'mod_schachaufgaben_bestenliste';

	/**
	 * Sortiert nach der höchsten je erreichten Wertung.
	 *
	 * @return string Spaltenname mit Tabellenkürzel „s."
	 */
	protected function sortierspalte(): string
	{
		return 's.bestWertung';
	}

	/**
	 * In der Liste steht, wer schon einmal eine gesicherte Wertung hatte.
	 *
	 * @return array{0: string, 1: array<int, mixed>} SQL-Bedingung und ihre Parameter
	 */
	protected function bedingung(): array
	{
		return array(
			"s.bestWertung > 0 AND m.disable = '' AND (m.stop = '' OR m.stop > ?)",
			array(time()),
		);
	}

	/**
	 * Ohne gesicherte Wertung fehlt noch etwas; wie viele Aufgaben das sind,
	 * lässt sich nicht sagen, weil es von den Ergebnissen abhängt.
	 *
	 * @param array<string, mixed> $zeile Die Zeile des angemeldeten Mitglieds
	 *
	 * @return int 1, solange es noch keine gesicherte Wertung gab, sonst 0
	 */
	protected function fehlen(array $zeile): int
	{
		return (float) $zeile['bestWertung'] > 0 ? 0 : 1;
	}
}
