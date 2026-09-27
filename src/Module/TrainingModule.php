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
 * Frontend-Modul „Schachaufgaben-Training".
 *
 * Das Modul gibt nur das Gerüst aus: Brett, Anzeige und Knöpfe. Alles Weitere
 * erledigt training.js über die JSON-Schnittstelle (TrainingController). Das
 * klassische Modul über $GLOBALS['FE_MOD'] läuft unter Contao 4.13 und 5.7
 * ohne Versionsweiche; ein Fragment-Controller hätte in beiden Fassungen
 * verschiedene Signaturen.
 */
class TrainingModule extends Module
{
	/**
	 * @var string
	 */
	protected $strTemplate = 'mod_schachaufgaben_training';

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
			$template->wildcard = '### '.mb_strtoupper($GLOBALS['TL_LANG']['FMD']['schachaufgaben_training'][0] ?? 'Schachaufgaben-Training').' ###';
			$template->title = $this->headline;
			$template->id = $this->id;
			$template->link = $this->name;
			$template->href = 'contao?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

			return $template->parse();
		}

		return parent::generate();
	}

	/**
	 * Stellt Adressen, Texte und Stylesheets für das Template bereit.
	 *
	 * Die Adressen werden über den Router erzeugt, damit ein Contao in einem
	 * Unterverzeichnis funktioniert. Die Texte stehen in den Sprachdateien und
	 * gehen als JSON an das Skript, damit es selbst keine Texte enthält.
	 */
	protected function compile(): void
	{
		System::loadLanguageFile('default');
		System::loadLanguageFile('schachaufgaben_eroeffnungen');

		$container = System::getContainer();
		$router = $container->get('router');
		$request = $container->get('request_stack')->getCurrentRequest();
		$basis = (null === $request ? '' : $request->getBasePath()).'/bundles/contaoschachaufgaben/';

		$GLOBALS['TL_CSS'][] = 'bundles/contaoschachaufgaben/vendor/cm-chessboard/assets/chessboard.css';
		$GLOBALS['TL_CSS'][] = 'bundles/contaoschachaufgaben/vendor/cm-chessboard/assets/extensions/markers/markers.css';
		$GLOBALS['TL_CSS'][] = 'bundles/contaoschachaufgaben/vendor/cm-chessboard/assets/extensions/promotion-dialog/promotion-dialog.css';
		$GLOBALS['TL_CSS'][] = 'bundles/contaoschachaufgaben/training.css';

		// Versionsangabe aus dem Änderungsdatum: Viele Server liefern Dateien
		// unter /bundles mit einem Jahr Cache-Dauer aus, ohne Angabe liefe nach
		// einem Update weiter das alte Skript (so bei 1.1.0 geschehen).
		// training.js reicht die Angabe an eroeffnung.js weiter.
		$this->Template->skript = $basis.'training.js?v='.$this->skriptVersion();
		$this->Template->texte = $GLOBALS['TL_LANG']['MSC']['schachaufgaben'] ?? array();
		$this->Template->konfiguration = array(
			'aufgabeUrl'   => $router->generate('schachaufgaben_aufgabe'),
			'ergebnisUrl'  => $router->generate('schachaufgaben_ergebnis'),
			'bewertungUrl' => $router->generate('schachaufgaben_bewertung'),
			'assetsUrl'    => $basis.'vendor/cm-chessboard/assets/',
			'texte'        => $GLOBALS['TL_LANG']['MSC']['schachaufgaben'] ?? array(),
			'motive'       => $GLOBALS['TL_LANG']['MSC']['schachaufgaben_motive'] ?? array(),
			'eroeffnungen' => $GLOBALS['TL_LANG']['MSC']['schachaufgaben_eroeffnungen'] ?? array(),
		);
	}

	/**
	 * Bildet eine kurze Versionsangabe aus dem Änderungsdatum der eigenen Skripte.
	 *
	 * Gelesen wird aus Resources/public des Bundles, nicht aus dem öffentlichen
	 * Verzeichnis, weil das je nach Installation eine Kopie oder ein Verweis ist.
	 *
	 * @return string Acht Hexadezimalzeichen, die sich mit jeder Änderung der Skripte ändern
	 */
	private function skriptVersion(): string
	{
		$verzeichnis = __DIR__.'/../Resources/public/';
		$stand = '';

		foreach (array('training.js', 'eroeffnung.js') as $datei) {
			$stand .= $datei.'@'.(@filemtime($verzeichnis.$datei) ?: 0).';';
		}

		return substr(md5($stand), 0, 8);
	}
}
