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
 * Entscheidet, welche Aufgaben aus der Lichess-Sammlung übernommen werden.
 *
 * Die Sammlung enthält Millionen Aufgaben, darunter viele selten gespielte
 * oder schlecht bewertete. Die Filter sieben nach Beliebtheit, Anzahl der
 * Spiele, Schwierigkeit und Motiven.
 */
class ImportFilter
{
	private int $minBeliebtheit;

	private int $minSpiele;

	private int $minWertung;

	private int $maxWertung;

	/**
	 * @var array<int, string>
	 */
	private array $motive;

	/**
	 * Legt die Grenzen fest. Alle Grenzen sind einschließlich.
	 *
	 * @param int                $minBeliebtheit Untergrenze der Beliebtheit (-100 bis 100)
	 * @param int                $minSpiele      Mindestzahl der Lichess-Spiele
	 * @param int                $minWertung     Untergrenze der Wertungszahl
	 * @param int                $maxWertung     Obergrenze der Wertungszahl
	 * @param array<int, string> $motive         Die Aufgabe muss mindestens eines
	 *                                           dieser Motive tragen; leer = alle
	 */
	public function __construct(int $minBeliebtheit = -100, int $minSpiele = 0, int $minWertung = 0, int $maxWertung = 9999, array $motive = array())
	{
		$this->minBeliebtheit = $minBeliebtheit;
		$this->minSpiele = $minSpiele;
		$this->minWertung = $minWertung;
		$this->maxWertung = $maxWertung;
		$this->motive = $motive;
	}

	/**
	 * Prüft, ob eine Aufgabe alle Filter erfüllt.
	 *
	 * @param array<string, int|string> $aufgabe Eine Zeile aus dem LichessCsvLeser
	 *
	 * @return bool true, wenn die Aufgabe übernommen werden soll
	 */
	public function passt(array $aufgabe): bool
	{
		if ($aufgabe['beliebtheit'] < $this->minBeliebtheit || $aufgabe['spiele'] < $this->minSpiele) {
			return false;
		}

		if ($aufgabe['wertung'] < $this->minWertung || $aufgabe['wertung'] > $this->maxWertung) {
			return false;
		}

		if (array() === $this->motive) {
			return true;
		}

		$vorhanden = preg_split('/\s+/', (string) $aufgabe['motive'], -1, PREG_SPLIT_NO_EMPTY) ?: array();

		return array() !== array_intersect($this->motive, $vorhanden);
	}
}
