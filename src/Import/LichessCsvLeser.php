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
 * Liest die Aufgabensammlung von Lichess (lichess_db_puzzle.csv) in einem Zug.
 *
 * Die Datei hat mehrere Millionen Zeilen. Sie wird deshalb Zeile für Zeile
 * gelesen und als Generator weitergereicht, statt sie in den Speicher zu laden.
 * Für den Konsolenbefehl; der Import im Backend arbeitet mit LichessCsvDatei
 * direkt, weil er an einer gemerkten Stelle fortsetzen muss.
 */
class LichessCsvLeser
{
	/**
	 * Liest die Datei und liefert jede Datenzeile als Feld-Array.
	 *
	 * @param string $pfad Pfad zur entpackten CSV-Datei, oder „-" für die
	 *                     Standardeingabe (etwa „zstd -dc datei.csv.zst |")
	 *
	 * @throws \RuntimeException Wenn die Datei nicht lesbar ist, noch gepackt
	 *                           ist oder Pflichtspalten fehlen
	 *
	 * @return \Generator<int, array<string, int|string>> Schlüssel ist die
	 *                                                     Zeilennummer in der
	 *                                                     Datei; kaputte Zeilen
	 *                                                     tragen „_fehler"
	 */
	public function lesen(string $pfad): \Generator
	{
		$datei = new LichessCsvDatei($pfad);

		try {
			while (null !== ($eintrag = $datei->naechste())) {
				yield $eintrag[0] => $eintrag[1];
			}
		} finally {
			$datei->schliessen();
		}
	}
}
