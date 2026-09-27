<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Tests\Sprache;

use PHPUnit\Framework\TestCase;

/**
 * Prüft die Sprachdateien gegen die Lichess-Sammlung und gegeneinander.
 *
 * Die Listen in tests/Fixtures stammen aus der Sammlung vom September 2026:
 * alle 73 Motive und alle 156 Eröffnungsfamilien, die dort vorkommen.
 */
class SprachdateienTest extends TestCase
{
	private const SPRACHEN = __DIR__.'/../../src/Resources/contao/languages';

	/**
	 * Jedes Motiv der Sammlung hat einen deutschen und einen englischen Namen.
	 */
	public function testAlleMotiveSindUebersetzt(): void
	{
		$motive = $this->liste('lichess_motive.txt');

		foreach (array('de', 'en') as $sprache) {
			$uebersetzt = $this->laden($sprache, 'default')['MSC']['schachaufgaben_motive'];
			$this->assertSame(array(), array_values(array_diff($motive, array_keys($uebersetzt))), "Fehlende Motive ($sprache)");
		}
	}

	/**
	 * Jede Eröffnungsfamilie der Sammlung hat einen deutschen Namen.
	 */
	public function testAlleEroeffnungsfamilienSindUebersetzt(): void
	{
		$familien = $this->laden('de', 'schachaufgaben_eroeffnungen')['MSC']['schachaufgaben_eroeffnungen']['familien'];

		$this->assertSame(array(), array_values(array_diff($this->liste('lichess_eroeffnungsfamilien.txt'), array_keys($familien))));
	}

	/**
	 * Deutsch und Englisch haben dieselben Schlüssel, damit keine Beschriftung
	 * in einer Sprache fehlt.
	 *
	 * @dataProvider sprachdateien
	 */
	public function testDeutschUndEnglischHabenDieselbenSchluessel(string $datei): void
	{
		$this->assertSame(
			$this->schluessel($this->laden('de', $datei)),
			$this->schluessel($this->laden('en', $datei))
		);
	}

	/**
	 * Liefert die Sprachdateien, die es in beiden Sprachen geben muss.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function sprachdateien(): array
	{
		return array(
			'default'           => array('default'),
			'modules'           => array('modules'),
			'tl_module'         => array('tl_module'),
			'tl_schachaufgaben' => array('tl_schachaufgaben'),
		);
	}

	/**
	 * Lädt eine Sprachdatei in einen leeren $GLOBALS['TL_LANG'].
	 *
	 * @param string $sprache Sprachkürzel, etwa „de"
	 * @param string $datei   Dateiname ohne Endung
	 *
	 * @return array<string, mixed> Der Inhalt von $GLOBALS['TL_LANG']
	 */
	private function laden(string $sprache, string $datei): array
	{
		$GLOBALS['TL_LANG'] = array();
		include self::SPRACHEN."/$sprache/$datei.php";
		$inhalt = $GLOBALS['TL_LANG'];
		unset($GLOBALS['TL_LANG']);

		return $inhalt;
	}

	/**
	 * Liest eine Liste aus tests/Fixtures, ein Eintrag je Zeile.
	 *
	 * @param string $datei Dateiname
	 *
	 * @return array<int, string> Die Einträge ohne Leerzeilen
	 */
	private function liste(string $datei): array
	{
		return array_values(array_filter(array_map('trim', file(__DIR__.'/../Fixtures/'.$datei))));
	}

	/**
	 * Sammelt alle Schlüsselpfade eines verschachtelten Arrays.
	 *
	 * Listen mit Beschriftung und Erklärung (Index 0 und 1) zählen als Blatt,
	 * damit nur die benannten Schlüssel verglichen werden.
	 *
	 * @param array<mixed> $werte   Das Array
	 * @param string       $vorsilbe Pfad bis hierher
	 *
	 * @return array<int, string> Sortierte Pfade wie „tl_module.schachaufgaben_legend"
	 */
	private function schluessel(array $werte, string $vorsilbe = ''): array
	{
		$pfade = array();

		foreach ($werte as $schluessel => $wert) {
			$pfad = $vorsilbe.$schluessel;

			if (\is_array($wert) && !array_is_list($wert)) {
				$pfade = array_merge($pfade, $this->schluessel($wert, $pfad.'.'));
			} else {
				$pfade[] = $pfad;
			}
		}

		sort($pfade);

		return $pfade;
	}
}
