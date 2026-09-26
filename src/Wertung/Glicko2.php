<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Wertung;

/**
 * Wertungsberechnung nach Glicko-2 (Mark Glickman, „Example of the Glicko-2
 * system", 2013).
 *
 * Wie bei Lichess treten Spieler und Aufgabe gegeneinander an: Löst der
 * Spieler die Aufgabe, gewinnt er, sonst gewinnt die Aufgabe. Jeder Versuch
 * ist ein eigener Wertungszeitraum mit genau einer Partie. Dadurch passt sich
 * die Schwierigkeit laufend an: Wer Aufgaben löst, steigt und bekommt danach
 * schwerere, wer scheitert, bekommt leichtere.
 */
class Glicko2
{
	/**
	 * Umrechnungsfaktor zwischen Elo-Skala und Glicko-2-Skala (400 / ln 10).
	 */
	private const SKALA = 173.7178;

	/**
	 * Abbruchgenauigkeit der Volatilitäts-Iteration.
	 */
	private const EPSILON = 0.000001;

	private float $tau;

	private float $minAbweichung;

	private float $maxAbweichung;

	/**
	 * Legt die Systemkonstanten fest.
	 *
	 * @param float $tau           Begrenzt, wie schnell sich die Volatilität
	 *                             ändert. Glickman empfiehlt 0,3 bis 1,2; 0,5
	 *                             ist ein üblicher Mittelwert.
	 * @param float $minAbweichung Untergrenze der Abweichung. Ohne sie würde
	 *                             die Wertung vielspielender Spieler praktisch
	 *                             einfrieren; Lichess nutzt ebenfalls eine Untergrenze.
	 * @param float $maxAbweichung Obergrenze der Abweichung, entspricht der
	 *                             Unsicherheit eines neuen Spielers
	 */
	public function __construct(float $tau = 0.5, float $minAbweichung = 45.0, float $maxAbweichung = 350.0)
	{
		$this->tau = $tau;
		$this->minAbweichung = $minAbweichung;
		$this->maxAbweichung = $maxAbweichung;
	}

	/**
	 * Berechnet die neue Wertung nach einem Versuch.
	 *
	 * @param Wertung $spieler  Die Wertung vor dem Versuch
	 * @param Wertung $gegner   Die Wertung des Gegners (bzw. der Aufgabe) vor
	 *                          dem Versuch
	 * @param float   $ergebnis 1 für gewonnen (gelöst), 0 für verloren
	 *
	 * @return Wertung Die neue Wertung des Spielers
	 */
	public function versuch(Wertung $spieler, Wertung $gegner, float $ergebnis): Wertung
	{
		return $this->zeitraum($spieler, array(array($gegner, $ergebnis)));
	}

	/**
	 * Berechnet die neue Wertung nach einem Wertungszeitraum mit mehreren
	 * Partien (Schritte 2 bis 8 nach Glickman).
	 *
	 * @param Wertung                           $spieler Die Wertung vor dem Zeitraum
	 * @param array<int, array{Wertung, float}> $partien Je Partie der Gegner
	 *                                                   und das Ergebnis (1, 0,5
	 *                                                   oder 0)
	 *
	 * @return Wertung Die neue Wertung. Ohne Partien bleibt die Wertungszahl
	 *                 gleich, nur die Abweichung wächst (Schritt 6 ohne Partien).
	 */
	public function zeitraum(Wertung $spieler, array $partien): Wertung
	{
		// Schritt 2: in die Glicko-2-Skala umrechnen
		$mu = ($spieler->getWertung() - 1500) / self::SKALA;
		$phi = $spieler->getAbweichung() / self::SKALA;
		$sigma = $spieler->getVolatilitaet();

		if (array() === $partien) {
			$phiNeu = sqrt($phi ** 2 + $sigma ** 2);

			return new Wertung($spieler->getWertung(), $this->begrenzen($phiNeu * self::SKALA), $sigma);
		}

		// Schritte 3 und 4: geschätzte Varianz v und Verbesserung Δ
		$vKehrwert = 0.0;
		$summe = 0.0;

		foreach ($partien as [$gegner, $ergebnis]) {
			$muJ = ($gegner->getWertung() - 1500) / self::SKALA;
			$g = $this->g($gegner->getAbweichung() / self::SKALA);
			$e = 1 / (1 + exp(-$g * ($mu - $muJ)));

			$vKehrwert += $g ** 2 * $e * (1 - $e);
			$summe += $g * ($ergebnis - $e);
		}

		$v = 1 / $vKehrwert;
		$delta = $v * $summe;

		// Schritt 5: neue Volatilität
		$sigmaNeu = $this->volatilitaet($phi, $sigma, $v, $delta);

		// Schritte 6 und 7: neue Abweichung und Wertungszahl
		$phiStern = sqrt($phi ** 2 + $sigmaNeu ** 2);
		$phiNeu = 1 / sqrt(1 / $phiStern ** 2 + 1 / $v);
		$muNeu = $mu + $phiNeu ** 2 * $summe;

		// Schritt 8: zurück in die Elo-Skala
		return new Wertung(
			self::SKALA * $muNeu + 1500,
			$this->begrenzen(self::SKALA * $phiNeu),
			$sigmaNeu
		);
	}

	/**
	 * Gewichtungsfunktion g(φ): Je unsicherer die Wertung des Gegners, desto
	 * weniger zählt das Ergebnis.
	 *
	 * @param float $phi Abweichung des Gegners in der Glicko-2-Skala
	 *
	 * @return float Gewicht zwischen 0 und 1
	 */
	private function g(float $phi): float
	{
		return 1 / sqrt(1 + 3 * $phi ** 2 / M_PI ** 2);
	}

	/**
	 * Bestimmt die neue Volatilität mit dem Illinois-Verfahren (Schritt 5).
	 *
	 * @param float $phi   Abweichung in der Glicko-2-Skala
	 * @param float $sigma Bisherige Volatilität
	 * @param float $v     Geschätzte Varianz aus Schritt 3
	 * @param float $delta Geschätzte Verbesserung aus Schritt 4
	 *
	 * @return float Die neue Volatilität
	 */
	private function volatilitaet(float $phi, float $sigma, float $v, float $delta): float
	{
		$a = log($sigma ** 2);
		$tau = $this->tau;

		$f = static function (float $x) use ($phi, $v, $delta, $a, $tau): float {
			$ex = exp($x);

			return $ex * ($delta ** 2 - $phi ** 2 - $v - $ex) / (2 * ($phi ** 2 + $v + $ex) ** 2) - ($x - $a) / $tau ** 2;
		};

		$gA = $a;

		if ($delta ** 2 > $phi ** 2 + $v) {
			$gB = log($delta ** 2 - $phi ** 2 - $v);
		} else {
			$k = 1;

			while ($f($a - $k * $tau) < 0) {
				++$k;
			}

			$gB = $a - $k * $tau;
		}

		$fA = $f($gA);
		$fB = $f($gB);

		while (abs($gB - $gA) > self::EPSILON) {
			$gC = $gA + ($gA - $gB) * $fA / ($fB - $fA);
			$fC = $f($gC);

			if ($fC * $fB <= 0) {
				$gA = $gB;
				$fA = $fB;
			} else {
				$fA /= 2;
			}

			$gB = $gC;
			$fB = $fC;
		}

		return exp($gA / 2);
	}

	/**
	 * Hält die Abweichung zwischen Unter- und Obergrenze.
	 *
	 * @param float $abweichung Abweichung in der Elo-Skala
	 *
	 * @return float Die begrenzte Abweichung
	 */
	private function begrenzen(float $abweichung): float
	{
		return max($this->minAbweichung, min($this->maxAbweichung, $abweichung));
	}
}
