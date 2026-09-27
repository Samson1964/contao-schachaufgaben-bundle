<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Tests\Training;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSchachaufgabenBundle\Training\Anzeigename;
use Schachbulle\ContaoSchachaufgabenBundle\Training\Ranglistenstand;

/**
 * Prüft Stichtag und Anzeigename der Ranglisten.
 */
class RanglistenstandTest extends TestCase
{
	private string $zeitzone;

	/**
	 * Setzt eine feste Zeitzone, damit der Test überall gleich rechnet.
	 */
	protected function setUp(): void
	{
		$this->zeitzone = date_default_timezone_get();
		date_default_timezone_set('Europe/Berlin');
	}

	/**
	 * Stellt die ursprüngliche Zeitzone wieder her.
	 */
	protected function tearDown(): void
	{
		date_default_timezone_set($this->zeitzone);
	}

	/**
	 * Jeder Zeitpunkt eines Monats führt zum Monatsersten 0 Uhr, auch über die
	 * Umstellung auf Winterzeit und den Jahreswechsel.
	 *
	 * @dataProvider zeitpunkte
	 */
	public function testStichtagIstDerMonatserste(string $zeitpunkt, string $erwartet): void
	{
		$this->assertSame($erwartet, date('Y-m-d H:i:s', Ranglistenstand::stichtag((int) strtotime($zeitpunkt))));
	}

	/**
	 * Liefert Zeitpunkte und den erwarteten Stichtag.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function zeitpunkte(): array
	{
		return array(
			'Monatserster 0 Uhr'   => array('2026-10-01 00:00:00', '2026-10-01 00:00:00'),
			'Monatsletzter spät'   => array('2026-10-31 23:59:59', '2026-10-01 00:00:00'),
			'nach der Zeitumstellung' => array('2026-10-25 12:00:00', '2026-10-01 00:00:00'),
			'Jahreswechsel'        => array('2027-01-01 00:30:00', '2027-01-01 00:00:00'),
			'Silvester'            => array('2026-12-31 23:00:00', '2026-12-01 00:00:00'),
		);
	}

	/**
	 * Der öffentliche Name ist „Vorname N.".
	 */
	public function testAnzeigename(): void
	{
		$this->assertSame('Max M.', Anzeigename::kurz('Max', 'Mustermann'));
		$this->assertSame('Ölaf Ü.', Anzeigename::kurz(' Ölaf ', 'Übel'));
		$this->assertSame('Max', Anzeigename::kurz('Max', ''));
		$this->assertSame('–', Anzeigename::kurz('', null));
	}
}
