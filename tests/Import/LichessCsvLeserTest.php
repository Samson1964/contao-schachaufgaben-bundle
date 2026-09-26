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
use Schachbulle\ContaoSchachaufgabenBundle\Import\LichessCsvLeser;

/**
 * Prüft das Einlesen der Lichess-CSV anhand eines kleinen Auszugs.
 *
 * Der Auszug in tests/Fixtures/lichess_auszug.csv enthält fünf gültige
 * Aufgaben, eine Leerzeile, eine Zeile mit kaputter FEN (000bR) und eine
 * mit zu wenigen Spalten (000cQ).
 */
class LichessCsvLeserTest extends TestCase
{
	private const AUSZUG = __DIR__.'/../Fixtures/lichess_auszug.csv';

	/**
	 * Die Spalten werden auf die Tabellenfelder umbenannt, Zahlen als int.
	 */
	public function testZeileWirdAufTabellenfelderAbgebildet(): void
	{
		$zeilen = iterator_to_array((new LichessCsvLeser())->lesen(self::AUSZUG));

		$this->assertSame(
			array(
				'lichessId'         => '0009B',
				'fen'               => 'r2qr1k1/b1p2ppp/pp4n1/P1P1p3/4P1n1/B2P2Pb/3NBP1P/RN1QR1K1 b - - 1 16',
				'zuege'             => 'b6c5 e2g4 h3g4 d1g4',
				'wertung'           => 1128,
				'wertungAbweichung' => 81,
				'beliebtheit'       => 87,
				'spiele'            => 1050,
				'motive'            => 'advantage middlegame short',
				'partieUrl'         => 'https://lichess.org/4MWQCxQ6/black#32',
				'eroeffnung'        => 'Kings_Pawn_Game Kings_Pawn_Game_Leonardis_Variation',
			),
			$zeilen[4]
		);
	}

	/**
	 * Leerzeilen fallen weg, Zeilen mit falscher Spaltenzahl kommen mit
	 * Fehlerangabe; die Schlüssel sind die Zeilennummern der Datei.
	 */
	public function testLeerzeilenUndKaputteZeilen(): void
	{
		$zeilen = iterator_to_array((new LichessCsvLeser())->lesen(self::AUSZUG));

		$this->assertSame(array(2, 3, 4, 6, 7, 8, 9), array_keys($zeilen));
		$this->assertArrayHasKey('_fehler', $zeilen[8]);
		$this->assertSame(-12, $zeilen[6]['beliebtheit']);
	}

	/**
	 * Eine noch gepackte Datei wird mit einem Hinweis abgewiesen.
	 */
	public function testGepackteDateiWirdAbgewiesen(): void
	{
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Zstandard');

		iterator_to_array((new LichessCsvLeser())->lesen('lichess_db_puzzle.csv.zst'));
	}

	/**
	 * Eine Datei ohne die Pflichtspalten wird abgewiesen.
	 */
	public function testFremdeCsvWirdAbgewiesen(): void
	{
		$datei = tempnam(sys_get_temp_dir(), 'csv');
		file_put_contents($datei, "Name,Vorname\nHoppe,Frank\n");

		try {
			$this->expectException(\RuntimeException::class);
			$this->expectExceptionMessage('PuzzleId');

			iterator_to_array((new LichessCsvLeser())->lesen($datei));
		} finally {
			unlink($datei);
		}
	}

	/**
	 * Ein UTF-8-BOM vor der Kopfzeile stört die Spaltenzuordnung nicht.
	 */
	public function testBomWirdEntfernt(): void
	{
		$datei = tempnam(sys_get_temp_dir(), 'csv');
		file_put_contents($datei, "\xEF\xBB\xBF".file_get_contents(self::AUSZUG));

		try {
			$zeilen = iterator_to_array((new LichessCsvLeser())->lesen($datei));
			$this->assertSame('00008', $zeilen[2]['lichessId']);
		} finally {
			unlink($datei);
		}
	}
}
