<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Tests\Import;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSchachaufgabenBundle\Import\ImportFilter;

/**
 * Prüft die Auswahl der zu importierenden Aufgaben.
 */
class ImportFilterTest extends TestCase
{
	/**
	 * Ohne Grenzen wird jede Aufgabe übernommen, auch eine unbeliebte.
	 */
	public function testOhneGrenzenPasstAlles(): void
	{
		$this->assertTrue((new ImportFilter())->passt($this->aufgabe(array('beliebtheit' => -100, 'spiele' => 0))));
	}

	/**
	 * Die Grenzen gelten einschließlich.
	 */
	public function testGrenzenSindEinschliesslich(): void
	{
		$filter = new ImportFilter(80, 1000, 800, 2400);

		$this->assertTrue($filter->passt($this->aufgabe(array('beliebtheit' => 80, 'spiele' => 1000, 'wertung' => 800))));
		$this->assertTrue($filter->passt($this->aufgabe(array('beliebtheit' => 80, 'spiele' => 1000, 'wertung' => 2400))));
		$this->assertFalse($filter->passt($this->aufgabe(array('beliebtheit' => 79, 'spiele' => 1000))));
		$this->assertFalse($filter->passt($this->aufgabe(array('beliebtheit' => 80, 'spiele' => 999))));
		$this->assertFalse($filter->passt($this->aufgabe(array('beliebtheit' => 80, 'spiele' => 1000, 'wertung' => 799))));
		$this->assertFalse($filter->passt($this->aufgabe(array('beliebtheit' => 80, 'spiele' => 1000, 'wertung' => 2401))));
	}

	/**
	 * Von mehreren Motiven genügt eines; Teilwörter zählen nicht.
	 */
	public function testMotiveGenuegtEines(): void
	{
		$filter = new ImportFilter(-100, 0, 0, 9999, array('fork', 'mateIn2'));

		$this->assertTrue($filter->passt($this->aufgabe(array('motive' => 'endgame mateIn2 short'))));
		$this->assertTrue($filter->passt($this->aufgabe(array('motive' => 'fork'))));
		$this->assertFalse($filter->passt($this->aufgabe(array('motive' => 'mateIn2x pin'))));
		$this->assertFalse($filter->passt($this->aufgabe(array('motive' => ''))));
	}

	/**
	 * Baut eine Aufgabe mit mittleren Werten, die einzelne Felder überschreiben.
	 *
	 * @param array<string, int|string> $werte Abweichende Felder
	 *
	 * @return array<string, int|string> Eine Zeile wie vom LichessCsvLeser
	 */
	private function aufgabe(array $werte): array
	{
		return array_merge(
			array('wertung' => 1500, 'beliebtheit' => 90, 'spiele' => 5000, 'motive' => 'short'),
			$werte
		);
	}
}
