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
	 * den Moduleinstellungen (numberOfItems, schachaufgabenMinVersuche).
	 */
	protected function compile(): void
	{
		System::loadLanguageFile('default');

		$GLOBALS['TL_CSS'][] = 'bundles/contaoschachaufgaben/training.css';

		$anzahl = (int) $this->numberOfItems > 0 ? (int) $this->numberOfItems : 20;
		$minVersuche = max(0, (int) $this->schachaufgabenMinVersuche);

		$zeilen = System::getContainer()->get('database_connection')->fetchAllAssociative(
			sprintf(
				"SELECT s.wertung, s.versuche, s.geloest, m.firstname, m.lastname, m.username
				 FROM tl_schachaufgaben_spieler s
				 INNER JOIN tl_member m ON m.id = s.memberId
				 WHERE s.versuche >= ? AND m.disable = '' AND (m.stop = '' OR m.stop > ?)
				 ORDER BY s.wertung DESC
				 LIMIT %d",
				$anzahl
			),
			array($minVersuche, time())
		);

		// Der TokenChecker ist in beiden Fassungen ein öffentlicher Dienst
		$eigenerName = System::getContainer()->get('contao.security.token_checker')->getFrontendUsername();

		$liste = array();

		foreach ($zeilen as $platz => $zeile) {
			$liste[] = array(
				'platz'    => $platz + 1,
				'name'     => trim($zeile['firstname'].' '.mb_substr((string) $zeile['lastname'], 0, 1).'.'),
				'wertung'  => (int) round((float) $zeile['wertung']),
				'versuche' => (int) $zeile['versuche'],
				'quote'    => (int) $zeile['versuche'] > 0 ? (int) round(100 * $zeile['geloest'] / $zeile['versuche']) : 0,
				'eigene'   => null !== $eigenerName && $zeile['username'] === $eigenerName,
			);
		}

		$this->Template->liste = $liste;
		$this->Template->minVersuche = $minVersuche;
		$this->Template->texte = $GLOBALS['TL_LANG']['MSC']['schachaufgaben'] ?? array();
	}
}
