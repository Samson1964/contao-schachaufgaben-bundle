<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Module;

use Contao\BackendTemplate;
use Contao\Module;
use Contao\System;

/**
 * Frontend-Modul „Schachaufgaben-Rangliste": die Mitglieder mit der höchsten
 * Wertung.
 *
 * Aufgeführt werden nur Mitglieder mit einer Mindestzahl gewerteter
 * Versuche, weil die Wertung vorher zu unsicher ist. Gezeigt wird der Name
 * als „Vorname N.", damit die Rangliste keine vollen Namen veröffentlicht.
 * Das angemeldete Mitglied wird hervorgehoben.
 */
class RanglisteModule extends Module
{
	/**
	 * @var string
	 */
	protected $strTemplate = 'mod_schachaufgaben_rangliste';

	/**
	 * Gibt im Backend nur einen Platzhalter aus, im Frontend das Modul.
	 *
	 * @return string Das HTML des Moduls
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if (null !== $request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
			$template = new BackendTemplate('be_wildcard');
			$template->wildcard = '### '.mb_strtoupper($GLOBALS['TL_LANG']['FMD']['schachaufgaben_rangliste'][0] ?? 'Schachaufgaben-Rangliste').' ###';
			$template->title = $this->headline;
			$template->id = $this->id;
			$template->link = $this->name;
			$template->href = 'contao?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

			return $template->parse();
		}

		return parent::generate();
	}

	/**
	 * Liest die Rangliste aus der Datenbank.
	 *
	 * Gesperrte oder abgelaufene Mitglieder (disable, stop) werden nicht
	 * aufgeführt. Anzahl der Plätze und Mindestzahl der Versuche kommen aus
	 * den Moduleinstellungen (schachaufgabenPlaetze, schachaufgabenMinVersuche).
	 *
	 * Steht das angemeldete Mitglied nicht unter den gezeigten Plätzen, wird
	 * seine Zeile mit dem tatsächlichen Platz unter der Tabelle angehängt; hat
	 * es noch zu wenige Aufgaben gespielt, steht dort, wie viele noch fehlen.
	 */
	protected function compile(): void
	{
		System::loadLanguageFile('default');

		$GLOBALS['TL_CSS'][] = 'bundles/contaoschachaufgaben/training.css';

		$anzahl = (int) $this->schachaufgabenPlaetze > 0 ? (int) $this->schachaufgabenPlaetze : 20;
		$minVersuche = max(0, (int) $this->schachaufgabenMinVersuche);
		$db = System::getContainer()->get('database_connection');

		// Die Bedingung „darf in der Rangliste stehen" an einer Stelle
		$gewertet = "s.versuche >= ? AND m.disable = '' AND (m.stop = '' OR m.stop > ?)";

		$zeilen = $db->fetchAllAssociative(
			sprintf(
				'SELECT s.wertung, s.versuche, s.geloest, m.firstname, m.lastname, m.username
				 FROM tl_schachaufgaben_spieler s
				 INNER JOIN tl_member m ON m.id = s.memberId
				 WHERE %s
				 ORDER BY s.wertung DESC, s.versuche DESC
				 LIMIT %d',
				$gewertet,
				$anzahl
			),
			array($minVersuche, time())
		);

		// Der TokenChecker ist in beiden Fassungen ein öffentlicher Dienst
		$eigenerName = System::getContainer()->get('contao.security.token_checker')->getFrontendUsername();
		$liste = array();
		$gefunden = false;

		foreach ($zeilen as $index => $zeile) {
			$eintrag = $this->eintrag($zeile, $index + 1, $eigenerName);
			$gefunden = $gefunden || $eintrag['eigene'];
			$liste[] = $eintrag;
		}

		$eigene = null;

		if (null !== $eigenerName && !$gefunden) {
			$zeile = $db->fetchAssociative(
				'SELECT s.wertung, s.versuche, s.geloest, m.firstname, m.lastname, m.username
				 FROM tl_schachaufgaben_spieler s
				 INNER JOIN tl_member m ON m.id = s.memberId
				 WHERE m.username = ?',
				array($eigenerName)
			);

			if (false !== $zeile) {
				$fehlen = max(0, $minVersuche - (int) $zeile['versuche']);
				$platz = null;

				if (0 === $fehlen) {
					$platz = 1 + (int) $db->fetchOne(
						sprintf('SELECT COUNT(*) FROM tl_schachaufgaben_spieler s INNER JOIN tl_member m ON m.id = s.memberId WHERE %s AND s.wertung > ?', $gewertet),
						array($minVersuche, time(), $zeile['wertung'])
					);
				}

				$eigene = array('fehlen' => $fehlen) + $this->eintrag($zeile, $platz, $eigenerName);
			}
		}

		$this->Template->liste = $liste;
		$this->Template->eigene = $eigene;
		// Leerzeile nur, wenn der eigene Platz nicht unmittelbar an die Liste anschließt
		$this->Template->luecke = null !== $eigene && (null === $eigene['platz'] || $eigene['platz'] > \count($liste) + 1);
		$this->Template->minVersuche = $minVersuche;
		$this->Template->texte = $GLOBALS['TL_LANG']['MSC']['schachaufgaben'] ?? array();
	}

	/**
	 * Bereitet eine Zeile der Rangliste für das Template auf.
	 *
	 * @param array<string, mixed> $zeile       Zeile aus tl_schachaufgaben_spieler mit Mitgliedsdaten
	 * @param int|null             $platz       Platz in der Rangliste, oder null solange noch nicht gewertet
	 * @param string|null          $eigenerName Benutzername des angemeldeten Mitglieds, oder null
	 *
	 * @return array<string, mixed> platz, name („Vorname N."), wertung, versuche, quote und eigene
	 */
	private function eintrag(array $zeile, ?int $platz, ?string $eigenerName): array
	{
		$nachname = trim((string) $zeile['lastname']);

		return array(
			'platz'    => $platz,
			'name'     => trim($zeile['firstname'].('' !== $nachname ? ' '.mb_substr($nachname, 0, 1).'.' : '')),
			'wertung'  => (int) round((float) $zeile['wertung']),
			'versuche' => (int) $zeile['versuche'],
			'quote'    => (int) $zeile['versuche'] > 0 ? (int) round(100 * $zeile['geloest'] / $zeile['versuche']) : 0,
			'eigene'   => null !== $eigenerName && $zeile['username'] === $eigenerName,
		);
	}
}
