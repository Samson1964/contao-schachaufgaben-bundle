<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Tests\Wertung;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSchachaufgabenBundle\Wertung\Glicko2;
use Schachbulle\ContaoSchachaufgabenBundle\Wertung\Wertung;

/**
 * Prüft die Glicko-2-Berechnung.
 */
class Glicko2Test extends TestCase
{
	/**
	 * Rechenbeispiel aus Glickman, „Example of the Glicko-2 system":
	 * Spieler 1500/200/0,06 gewinnt gegen 1400/30, verliert gegen 1550/100
	 * und 1700/300; τ = 0,5. Ergebnis laut Beispiel: 1464,06 / 151,52 / 0,05999.
	 */
	public function testBeispielVonGlickman(): void
	{
		$glicko = new Glicko2(0.5, 0.0, 350.0);

		$neu = $glicko->zeitraum(new Wertung(1500, 200, 0.06), array(
			array(new Wertung(1400, 30), 1.0),
			array(new Wertung(1550, 100), 0.0),
			array(new Wertung(1700, 300), 0.0),
		));

		$this->assertEqualsWithDelta(1464.06, $neu->getWertung(), 0.01);
		$this->assertEqualsWithDelta(151.52, $neu->getAbweichung(), 0.01);
		$this->assertEqualsWithDelta(0.05999, $neu->getVolatilitaet(), 0.00001);
	}

	/**
	 * Gelöst lässt die Wertung steigen, nicht gelöst fallen — und bei einer
	 * schweren Aufgabe bringt das Lösen mehr als bei einer leichten.
	 */
	public function testSchwereAufgabeBringtMehr(): void
	{
		$glicko = new Glicko2();
		$spieler = new Wertung(1500, 100);

		$leicht = $glicko->versuch($spieler, new Wertung(1200, 80), 1.0)->getWertung() - 1500;
		$schwer = $glicko->versuch($spieler, new Wertung(1800, 80), 1.0)->getWertung() - 1500;
		$verloren = $glicko->versuch($spieler, new Wertung(1500, 80), 0.0)->getWertung() - 1500;

		$this->assertGreaterThan(0, $leicht);
		$this->assertGreaterThan($leicht, $schwer);
		$this->assertLessThan(0, $verloren);
	}

	/**
	 * Nach vielen Versuchen wird die Wertung sicherer, aber nie sicherer als
	 * die Untergrenze; bei ausgeglichenen Ergebnissen bleibt sie in der Mitte.
	 */
	public function testAbweichungSinktUndBleibtInDenGrenzen(): void
	{
		$glicko = new Glicko2(0.5, 45.0, 350.0);
		$spieler = new Wertung();

		for ($i = 0; $i < 500; ++$i) {
			$spieler = $glicko->versuch($spieler, new Wertung(1500, 60), (float) ($i % 2));
			$this->assertGreaterThanOrEqual(45.0, $spieler->getAbweichung());
		}

		$this->assertLessThan(100, $spieler->getAbweichung());
		$this->assertEqualsWithDelta(1500, $spieler->getWertung(), 50);

		// Die Untergrenze greift, wenn sie über dem natürlichen Gleichgewicht liegt
		$begrenzt = (new Glicko2(0.5, 120.0, 350.0))->versuch($spieler, new Wertung(1500, 60), 1.0);
		$this->assertSame(120.0, $begrenzt->getAbweichung());
	}

	/**
	 * Ohne Partien wächst nur die Abweichung.
	 */
	public function testOhnePartienWaechstNurDieAbweichung(): void
	{
		$neu = (new Glicko2())->zeitraum(new Wertung(1700, 100, 0.06), array());

		$this->assertSame(1700.0, $neu->getWertung());
		$this->assertGreaterThan(100, $neu->getAbweichung());
	}
}
