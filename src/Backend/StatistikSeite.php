<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Backend;

use Contao\BackendTemplate;
use Contao\System;
use Schachbulle\ContaoSchachaufgabenBundle\Training\Statistik;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Statistik der Zugriffe und gespielten Aufgaben (do=schachaufgaben&key=statistik).
 *
 * Aufbau nach der Zugriffsstatistik des counter-Bundles: Ebenen Tag, Monat und
 * Jahr, Blättern mit „zurück", „vor" und „bis heute", Kennzahlen, Diagramme
 * eine Ebene feiner (Jahr → Monate, Monat → Tage, Tag → Stunden) und
 * Ranglisten als Tabelle.
 *
 * Öffentlicher Dienst, weil Contao den key-Callback über
 * System::importStatic() aus dem Container holt.
 */
class StatistikSeite
{
	private const EBENEN = array('tag', 'monat', 'jahr');

	/**
	 * Zeilen der beiden Ranglisten.
	 */
	private const TOP = 20;

	/**
	 * Übernimmt die benötigten Dienste.
	 *
	 * @param Statistik    $statistik    Liefert Zähler, Verläufe und Ranglisten
	 * @param RequestStack $requestStack Liefert Ebene und Datum aus der Adresse
	 */
	public function __construct(private readonly Statistik $statistik, private readonly RequestStack $requestStack)
	{
	}

	/**
	 * Baut die Statistikseite.
	 *
	 * @return string Das HTML der Seite
	 */
	public function ausfuehren(): string
	{
		System::loadLanguageFile('default');
		System::loadLanguageFile('tl_schachaufgaben');
		$GLOBALS['TL_CSS'][] = 'bundles/contaoschachaufgaben/backend.css';

		$request = $this->requestStack->getCurrentRequest();
		$ebene = null === $request ? 'monat' : (string) $request->query->get('ebene', 'monat');
		$ebene = \in_array($ebene, self::EBENEN, true) ? $ebene : 'monat';
		$zeitpunkt = $this->zeitpunkt($request);
		$texte = $GLOBALS['TL_LANG']['tl_schachaufgaben']['statistik_seite'] ?? array();

		[$von, $bis, $beginn, $ende] = $this->zeitraum($ebene, $zeitpunkt);
		$einheit = array('tag' => 'stunde', 'monat' => 'tag', 'jahr' => 'monat')[$ebene];
		$achse = $this->achse($ebene, $zeitpunkt);

		$summen = $this->statistik->summen($von, $bis);
		$verlauf = array();

		foreach (array(Statistik::AUFRUF, Statistik::BEGONNEN, Statistik::GELOEST) as $art) {
			$verlauf[$art] = $this->statistik->verlauf($art, $von, $bis, $einheit);
		}

		$balkenAufrufe = array();
		$balkenGeloest = array();

		foreach ($achse as $schluessel => $titel) {
			$balkenAufrufe[] = array('titel' => $titel, 'wert' => $verlauf[Statistik::BEGONNEN][$schluessel] ?? 0, 'wert2' => $verlauf[Statistik::AUFRUF][$schluessel] ?? 0);
			$balkenGeloest[] = array('titel' => $titel, 'wert' => $verlauf[Statistik::GELOEST][$schluessel] ?? 0, 'wert2' => $verlauf[Statistik::BEGONNEN][$schluessel] ?? 0);
		}

		$gewertet = $summen[Statistik::GELOEST]['gesamt'] + $summen[Statistik::NICHT_GELOEST]['gesamt'];
		$vor = $this->verschieben($ebene, $zeitpunkt, 1);

		$template = new BackendTemplate('be_schachaufgaben_statistik');
		$template->texte = $texte;
		$template->zeitraum = $this->bezeichnung($ebene, $zeitpunkt);
		$template->summen = $summen;
		$template->quote = $gewertet > 0 ? (int) round(100 * $summen[Statistik::GELOEST]['gesamt'] / $gewertet) : null;
		$template->bestand = $this->statistik->bestand($beginn, $ende);
		$template->hatDaten = $summen[Statistik::AUFRUF]['gesamt'] + $summen[Statistik::BEGONNEN]['gesamt'] > 0;
		$template->diagrammAufrufe = Diagramm::balken($balkenAufrufe, (string) ($texte['diagrammAufrufe'] ?? ''), 240, 'monat' === $ebene);
		$template->diagrammGeloest = Diagramm::balken($balkenGeloest, (string) ($texte['diagrammGeloest'] ?? ''), 240, 'monat' === $ebene);
		$template->meistgespielt = $this->statistik->meistgespielt($beginn, $ende, self::TOP);
		$template->aktivste = $this->statistik->aktivsteMitglieder($beginn, $ende, self::TOP);
		$template->ebenenLinks = array_map(
			fn (string $e): array => array('url' => $this->url($request, $e, $zeitpunkt), 'text' => $texte['ebene_'.$e] ?? $e, 'aktiv' => $e === $ebene),
			self::EBENEN
		);
		$template->urlZurueck = $this->url($request, $ebene, $this->verschieben($ebene, $zeitpunkt, -1));
		$template->urlVor = $this->url($request, $ebene, $vor);
		$template->urlHeute = $this->url($request, $ebene, time());
		$template->kannVor = $vor <= time();
		$template->urlModul = (null === $request ? '' : $request->getBaseUrl().$request->getPathInfo()).'?do=schachaufgaben';

		return $template->parse();
	}

	/**
	 * Liest das Datum aus der Adresse; fehlt es, ist es ungültig oder liegt
	 * es in der Zukunft, gilt heute.
	 *
	 * @param Request|null $request Die Anfrage
	 *
	 * @return int Unix-Zeitstempel, 12 Uhr des gewählten Tags (sicher gegen Zeitumstellungen)
	 */
	private function zeitpunkt(?Request $request): int
	{
		$datum = null === $request ? '' : (string) $request->query->get('datum', '');

		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $datum, $teile) && checkdate((int) $teile[2], (int) $teile[3], (int) $teile[1])) {
			$zeit = (int) mktime(12, 0, 0, (int) $teile[2], (int) $teile[3], (int) $teile[1]);

			return min($zeit, (int) mktime(12, 0, 0));
		}

		return (int) mktime(12, 0, 0);
	}

	/**
	 * Grenzen des Zeitraums als JJJJMMTT und als Zeitstempel.
	 *
	 * @param string $ebene     tag, monat oder jahr
	 * @param int    $zeitpunkt Ein Zeitpunkt im Zeitraum
	 *
	 * @return array{0: int, 1: int, 2: int, 3: int} von, bis (JJJJMMTT, einschließlich),
	 *                                               Beginn (einschließlich) und Ende (ausschließlich) als Zeitstempel
	 */
	private function zeitraum(string $ebene, int $zeitpunkt): array
	{
		$j = (int) date('Y', $zeitpunkt);
		$m = (int) date('n', $zeitpunkt);
		$t = (int) date('j', $zeitpunkt);

		return match ($ebene) {
			'tag'   => array((int) date('Ymd', $zeitpunkt), (int) date('Ymd', $zeitpunkt), (int) mktime(0, 0, 0, $m, $t, $j), (int) mktime(0, 0, 0, $m, $t + 1, $j)),
			'jahr'  => array($j * 10000 + 101, $j * 10000 + 1231, (int) mktime(0, 0, 0, 1, 1, $j), (int) mktime(0, 0, 0, 1, 1, $j + 1)),
			default => array($j * 10000 + $m * 100 + 1, $j * 10000 + $m * 100 + 31, (int) mktime(0, 0, 0, $m, 1, $j), (int) mktime(0, 0, 0, $m + 1, 1, $j)),
		};
	}

	/**
	 * Die vollständige Achse des Diagramms, damit leere Zeitpunkte als Lücke erscheinen.
	 *
	 * @param string $ebene     tag, monat oder jahr
	 * @param int    $zeitpunkt Ein Zeitpunkt im Zeitraum
	 *
	 * @return array<int, string> Stunde, Tag bzw. Monat => Beschriftung
	 */
	private function achse(string $ebene, int $zeitpunkt): array
	{
		$achse = array();

		if ('tag' === $ebene) {
			for ($s = 0; $s < 24; ++$s) {
				$achse[$s] = (string) $s;
			}
		} elseif ('jahr' === $ebene) {
			for ($m = 1; $m <= 12; ++$m) {
				$achse[$m] = mb_substr((string) ($GLOBALS['TL_LANG']['MONTHS'][$m - 1] ?? $m), 0, 3);
			}
		} else {
			$tage = (int) date('t', $zeitpunkt);

			for ($t = 1; $t <= $tage; ++$t) {
				$achse[$t] = sprintf('%02d.%s', $t, date('m.', $zeitpunkt));
			}
		}

		return $achse;
	}

	/**
	 * Verschiebt den Zeitpunkt um einen Zeitraum der Ebene.
	 *
	 * @param string $ebene     tag, monat oder jahr
	 * @param int    $zeitpunkt Ausgangspunkt
	 * @param int    $richtung  -1 zurück, 1 vor
	 *
	 * @return int Neuer Zeitpunkt, 12 Uhr; beim Monat der Erste, damit es keine Überläufe gibt
	 */
	private function verschieben(string $ebene, int $zeitpunkt, int $richtung): int
	{
		$j = (int) date('Y', $zeitpunkt);
		$m = (int) date('n', $zeitpunkt);
		$t = (int) date('j', $zeitpunkt);

		return (int) match ($ebene) {
			'tag'   => mktime(12, 0, 0, $m, $t + $richtung, $j),
			'jahr'  => mktime(12, 0, 0, 1, 1, $j + $richtung),
			default => mktime(12, 0, 0, $m + $richtung, 1, $j),
		};
	}

	/**
	 * Lesbare Bezeichnung des Zeitraums, etwa „28.09.2026", „September 2026" oder „2026".
	 *
	 * @param string $ebene     tag, monat oder jahr
	 * @param int    $zeitpunkt Ein Zeitpunkt im Zeitraum
	 *
	 * @return string Die Bezeichnung
	 */
	private function bezeichnung(string $ebene, int $zeitpunkt): string
	{
		return match ($ebene) {
			'tag'   => date('d.m.Y', $zeitpunkt),
			'jahr'  => date('Y', $zeitpunkt),
			default => ($GLOBALS['TL_LANG']['MONTHS'][(int) date('n', $zeitpunkt) - 1] ?? date('m')).' '.date('Y', $zeitpunkt),
		};
	}

	/**
	 * Adresse der Statistik für eine Ebene und einen Zeitpunkt.
	 *
	 * @param Request|null $request   Die Anfrage (für Basisadresse und ref)
	 * @param string       $ebene     tag, monat oder jahr
	 * @param int          $zeitpunkt Ein Zeitpunkt im Zeitraum
	 *
	 * @return string Maskierte Adresse für das Template
	 */
	private function url(?Request $request, string $ebene, int $zeitpunkt): string
	{
		$basis = null === $request ? '' : $request->getBaseUrl().$request->getPathInfo();

		return htmlspecialchars($basis.'?'.http_build_query(array(
			'do'    => 'schachaufgaben',
			'key'   => 'statistik',
			'ebene' => $ebene,
			'datum' => date('Y-m-d', $zeitpunkt),
			'ref'   => null === $request ? '' : (string) $request->query->get('ref', ''),
		)), ENT_QUOTES);
	}
}
