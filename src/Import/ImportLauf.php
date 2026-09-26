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
 * Import der Lichess-Sammlung in Häppchen für das Backend.
 *
 * Ein Import über die ganze Datei dauert länger, als viele Webserver einen
 * Aufruf laufen lassen. Deshalb verarbeitet schritt() nur so viele Zeilen,
 * wie in die vorgegebene Zeit passen, und gibt einen Zustand zurück, der in
 * der Sitzung liegt und beim nächsten Aufruf weitergereicht wird. Der Zustand
 * ist ein schlichtes Array, damit er sich ohne Weiteres speichern lässt.
 */
class ImportLauf
{
	/**
	 * Höchstzahl gemerkter Beispiele für fehlerhafte Zeilen.
	 */
	private const MAX_FEHLERBEISPIELE = 20;

	private AufgabenPruefer $pruefer;

	private AufgabenSchreiber $schreiber;

	/**
	 * Übernimmt Prüfer und Schreiber.
	 *
	 * @param AufgabenPruefer   $pruefer   Prüft FEN und Zugfolge
	 * @param AufgabenSchreiber $schreiber Schreibt blockweise in die Datenbank
	 */
	public function __construct(AufgabenPruefer $pruefer, AufgabenSchreiber $schreiber)
	{
		$this->pruefer = $pruefer;
		$this->schreiber = $schreiber;
	}

	/**
	 * Legt den Anfangszustand eines Imports an und prüft dabei die Datei.
	 *
	 * @param string               $pfad     Absoluter Pfad zur CSV-Datei
	 * @param array<string, mixed> $optionen minBeliebtheit, minSpiele,
	 *                                       minWertung, maxWertung (int),
	 *                                       motive (string[]), limit (int,
	 *                                       0 = alle), aktualisieren,
	 *                                       wertungUebernehmen,
	 *                                       veroeffentlichen (bool)
	 *
	 * @throws \RuntimeException Wenn die Datei nicht passt (siehe LichessCsvDatei)
	 *
	 * @return array<string, mixed> Der Zustand für schritt()
	 */
	public function beginnen(string $pfad, array $optionen): array
	{
		$datei = new LichessCsvDatei($pfad);
		$groesse = (int) $datei->groesse();
		$datei->schliessen();

		return array(
			'pfad'     => $pfad,
			'groesse'  => $groesse,
			'position' => 0,
			'zeile'    => 1,
			'fertig'   => false,
			'beginn'   => time(),
			'optionen' => array_merge(
				array(
					'minBeliebtheit'     => -100,
					'minSpiele'          => 0,
					'minWertung'         => 0,
					'maxWertung'         => 9999,
					'motive'             => array(),
					'limit'              => 0,
					'aktualisieren'      => false,
					'wertungUebernehmen' => false,
					'veroeffentlichen'   => true,
				),
				$optionen
			),
			'zaehler'  => array('gelesen' => 0, 'fehlerhaft' => 0, 'gefiltert' => 0, 'uebernommen' => 0, 'geschrieben' => 0),
			'fehler'   => array(),
		);
	}

	/**
	 * Verarbeitet Zeilen, bis die Zeit abgelaufen, das Limit erreicht oder
	 * die Datei zu Ende ist.
	 *
	 * Die Zeit wird nur zwischen zwei Zeilen geprüft; ein Häppchen kann also
	 * um die Dauer eines Datenbankblocks (500 Zeilen) länger laufen.
	 *
	 * @param array<string, mixed> $zustand   Zustand aus beginnen() oder dem
	 *                                        vorigen schritt()
	 * @param float                $sekunden  Zeitbudget dieses Häppchens
	 * @param int                  $maxZeilen Höchstens so viele Zeilen lesen;
	 *                                        vor allem für Tests, die ohne
	 *                                        Zeitabhängigkeit auskommen sollen
	 *
	 * @throws \RuntimeException Wenn die Datei nicht mehr lesbar ist
	 *
	 * @return array<string, mixed> Der neue Zustand; „fertig" ist true, wenn
	 *                              nichts mehr zu tun ist
	 */
	public function schritt(array $zustand, float $sekunden, int $maxZeilen = PHP_INT_MAX): array
	{
		if ($zustand['fertig']) {
			return $zustand;
		}

		$optionen = $zustand['optionen'];
		$ende = microtime(true) + $sekunden;
		$limit = (int) $optionen['limit'];

		$filter = new ImportFilter(
			(int) $optionen['minBeliebtheit'],
			(int) $optionen['minSpiele'],
			(int) $optionen['minWertung'],
			(int) $optionen['maxWertung'],
			(array) $optionen['motive']
		);

		$this->schreiber->einstellen((bool) $optionen['aktualisieren'], (bool) $optionen['wertungUebernehmen'], (bool) $optionen['veroeffentlichen']);

		$datei = new LichessCsvDatei($zustand['pfad']);
		$datei->springen((int) $zustand['position'], (int) $zustand['zeile']);
		$zaehler = $zustand['zaehler'];
		$gelesen = 0;

		try {
			while (microtime(true) < $ende && $gelesen++ < $maxZeilen) {
				if ($limit > 0 && $zaehler['uebernommen'] >= $limit) {
					$zustand['fertig'] = true;
					break;
				}

				$eintrag = $datei->naechste();

				if (null === $eintrag) {
					$zustand['fertig'] = true;
					break;
				}

				[$zeile, $aufgabe] = $eintrag;
				++$zaehler['gelesen'];

				$fehler = $aufgabe['_fehler'] ?? $this->pruefer->pruefeFen((string) $aufgabe['fen']) ?? $this->pruefer->pruefeZuege((string) $aufgabe['zuege']);

				if (null !== $fehler) {
					++$zaehler['fehlerhaft'];

					if (\count($zustand['fehler']) < self::MAX_FEHLERBEISPIELE) {
						$zustand['fehler'][] = sprintf('Zeile %d: %s', $zeile, $fehler);
					}

					continue;
				}

				if (!$filter->passt($aufgabe)) {
					++$zaehler['gefiltert'];
					continue;
				}

				$this->schreiber->hinzufuegen($aufgabe);
				++$zaehler['uebernommen'];
			}

			// Den Rest des Blocks schreiben. Erst danach rückt die gemerkte
			// Position vor: Schlägt das Schreiben fehl, bleibt der alte Zustand
			// gültig, und ein neuer Versuch liest dieselben Zeilen noch einmal.
			$zaehler['geschrieben'] += $this->schreiber->abschliessen();
			$zustand['position'] = $datei->position();
			$zustand['zeile'] = $datei->zeile();
		} finally {
			$datei->schliessen();
		}

		$zustand['zaehler'] = $zaehler;

		return $zustand;
	}

	/**
	 * Berechnet den Fortschritt für die Anzeige.
	 *
	 * Bei einem Limit zählt, was näher am Ziel ist: die gelesene Dateimenge
	 * oder der Anteil der übernommenen Aufgaben am Limit.
	 *
	 * @param array<string, mixed> $zustand Der aktuelle Zustand
	 *
	 * @return float Fortschritt zwischen 0 und 100
	 */
	public function prozent(array $zustand): float
	{
		if ($zustand['fertig']) {
			return 100.0;
		}

		$datei = $zustand['groesse'] > 0 ? 100 * $zustand['position'] / $zustand['groesse'] : 0.0;
		$limit = (int) $zustand['optionen']['limit'];
		$ziel = $limit > 0 ? 100 * $zustand['zaehler']['uebernommen'] / $limit : 0.0;

		return round(min(100.0, max($datei, $ziel)), 1);
	}
}
