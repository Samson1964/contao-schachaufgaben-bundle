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
use Schachbulle\ContaoSchachaufgabenBundle\Import\AufgabenPruefer;
use Schachbulle\ContaoSchachaufgabenBundle\Import\AufgabenSchreiber;
use Schachbulle\ContaoSchachaufgabenBundle\Import\ImportLauf;

/**
 * Prüft den Import in Häppchen: Egal wie klein die Häppchen sind, das
 * Ergebnis muss dasselbe sein wie bei einem Durchgang.
 */
class ImportLaufTest extends TestCase
{
	private const AUSZUG = __DIR__.'/../Fixtures/lichess_auszug.csv';

	/**
	 * Häppchen zu einer Zeile liefern dieselben Aufgaben in derselben
	 * Reihenfolge wie ein einziger Durchgang.
	 */
	public function testHaeppchenErgebenDasselbeWieEinDurchgang(): void
	{
		$ganz = $this->durchlaufen(PHP_INT_MAX, array());
		$einzeln = $this->durchlaufen(1, array());

		$this->assertSame(array('00008', '0000D', '0009B', '000aY', '000Vx'), $ganz['ids']);
		$this->assertSame($ganz['ids'], $einzeln['ids']);
		$this->assertSame($ganz['zustand']['zaehler'], $einzeln['zustand']['zaehler']);
		$this->assertSame(array('gelesen' => 7, 'fehlerhaft' => 2, 'gefiltert' => 0, 'uebernommen' => 5, 'geschrieben' => 0), $einzeln['zustand']['zaehler']);
		$this->assertSame(array('Zeile 7: Die FEN muss aus sechs durch Leerzeichen getrennten Feldern bestehen.', 'Zeile 8: 3 statt 10 Spalten'), $einzeln['zustand']['fehler']);
	}

	/**
	 * Filter und Limit wirken auch über Häppchengrenzen hinweg.
	 */
	public function testLimitUndFilterUeberHaeppchen(): void
	{
		$ergebnis = $this->durchlaufen(2, array('minBeliebtheit' => 80, 'limit' => 2));

		$this->assertSame(array('00008', '0000D'), $ergebnis['ids']);
		$this->assertTrue($ergebnis['zustand']['fertig']);
	}

	/**
	 * Der Fortschritt steigt und endet bei 100.
	 */
	public function testFortschritt(): void
	{
		$lauf = new ImportLauf(new AufgabenPruefer(), $this->createMock(AufgabenSchreiber::class));
		$zustand = $lauf->beginnen(self::AUSZUG, array());

		$this->assertSame(0.0, $lauf->prozent($zustand));

		$zustand = $lauf->schritt($zustand, 60.0, 3);
		$mitte = $lauf->prozent($zustand);
		$this->assertGreaterThan(0, $mitte);
		$this->assertLessThan(100, $mitte);

		$zustand = $lauf->schritt($zustand, 60.0);
		$this->assertSame(100.0, $lauf->prozent($zustand));
	}

	/**
	 * Lässt den Import in Häppchen der angegebenen Größe durchlaufen.
	 *
	 * @param int                  $zeilen   Zeilen je Häppchen
	 * @param array<string, mixed> $optionen Importoptionen
	 *
	 * @return array{ids: array<int, string>, zustand: array<string, mixed>}
	 *               Die Lichess-Kennungen in Schreibreihenfolge und der Endzustand
	 */
	private function durchlaufen(int $zeilen, array $optionen): array
	{
		$ids = array();
		$schreiber = $this->createMock(AufgabenSchreiber::class);
		$schreiber->method('hinzufuegen')->willReturnCallback(static function (array $aufgabe) use (&$ids): void {
			$ids[] = $aufgabe['lichessId'];
		});

		$lauf = new ImportLauf(new AufgabenPruefer(), $schreiber);
		$zustand = $lauf->beginnen(self::AUSZUG, $optionen);

		for ($runde = 0; !$zustand['fertig'] && $runde < 100; ++$runde) {
			$zustand = $lauf->schritt($zustand, 60.0, $zeilen);
		}

		return array('ids' => $ids, 'zustand' => $zustand);
	}
}
