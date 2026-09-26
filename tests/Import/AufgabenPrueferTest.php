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

/**
 * Prüft die formale Prüfung von FEN und UCI-Zugfolgen.
 */
class AufgabenPrueferTest extends TestCase
{
	/**
	 * Gültige Stellungen, darunter die Grundstellung, eine Lichess-Aufgabe
	 * und eine Stellung mit En-passant-Feld, werden ohne Fehler angenommen.
	 *
	 * @dataProvider gueltigeFens
	 */
	public function testGueltigeFenWirdAngenommen(string $fen): void
	{
		$this->assertNull((new AufgabenPruefer())->pruefeFen($fen));
	}

	/**
	 * Liefert gültige Stellungen für testGueltigeFenWirdAngenommen().
	 *
	 * @return array<string, array<int, string>>
	 */
	public function gueltigeFens(): array
	{
		return array(
			'Grundstellung'  => array('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'),
			'Lichess 00008'  => array('r6k/pp2r2p/4Rp1Q/3p4/8/1N1P2R1/PqP2bPP/7K b - - 0 24'),
			'En passant'     => array('rnbqkbnr/ppp1pppp/8/3pP3/8/8/PPPP1PPP/RNBQKBNR w KQkq d6 0 3'),
			'Nur Könige'     => array('8/8/8/8/8/8/8/K6k w - - 0 1'),
		);
	}

	/**
	 * Beschädigte Stellungen werden mit einer Fehlermeldung abgewiesen.
	 *
	 * @dataProvider ungueltigeFens
	 */
	public function testUngueltigeFenWirdAbgewiesen(string $fen): void
	{
		$this->assertIsString((new AufgabenPruefer())->pruefeFen($fen));
	}

	/**
	 * Liefert ungültige Stellungen für testUngueltigeFenWirdAbgewiesen().
	 *
	 * @return array<string, array<int, string>>
	 */
	public function ungueltigeFens(): array
	{
		return array(
			'Unsinn'              => array('kaputt'),
			'Nur Brett'           => array('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR'),
			'Sieben Reihen'       => array('rnbqkbnr/pppppppp/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'),
			'Reihe zu breit'      => array('rnbqkbnr/ppppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'),
			'Falsche Figur'       => array('rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQXBNR w KQkq - 0 1'),
			'Zwei weiße Könige'   => array('8/8/8/8/8/8/8/K5Kk w - - 0 1'),
			'Kein Zugrecht'       => array('8/8/8/8/8/8/8/K6k x - - 0 1'),
			'Rochade kaputt'      => array('8/8/8/8/8/8/8/K6k w QK - 0 1'),
			'En passant Reihe 4'  => array('8/8/8/8/8/8/8/K6k w - e4 0 1'),
			'Zugnummer 0'         => array('8/8/8/8/8/8/8/K6k w - - 0 0'),
		);
	}

	/**
	 * Gültige Zugfolgen, auch mit Umwandlung, werden angenommen.
	 */
	public function testGueltigeZuegeWerdenAngenommen(): void
	{
		$pruefer = new AufgabenPruefer();

		$this->assertNull($pruefer->pruefeZuege('f2g3 e6e7 b2b1 b3c1 b1c1 h6c1'));
		$this->assertNull($pruefer->pruefeZuege('  e7e8q   a1a2 '));
	}

	/**
	 * Zu kurze, ungerade oder falsch geschriebene Zugfolgen werden abgewiesen.
	 */
	public function testUngueltigeZuegeWerdenAbgewiesen(): void
	{
		$pruefer = new AufgabenPruefer();

		$this->assertIsString($pruefer->pruefeZuege(''));
		$this->assertIsString($pruefer->pruefeZuege('e2e4'));
		$this->assertIsString($pruefer->pruefeZuege('e2e4 e7e5 g1f3'));
		$this->assertIsString($pruefer->pruefeZuege('e2e4 Sf3'));
		$this->assertIsString($pruefer->pruefeZuege('e2e4 e7e8k'));
		$this->assertIsString($pruefer->pruefeZuege('e2e4 i7i8'));
	}
}
