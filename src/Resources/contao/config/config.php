<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

use Schachbulle\ContaoSchachaufgabenBundle\Backend\ImportSeite;
use Schachbulle\ContaoSchachaufgabenBundle\Model\SchachaufgabeModel;
use Schachbulle\ContaoSchachaufgabenBundle\Module\BestenlisteModule;
use Schachbulle\ContaoSchachaufgabenBundle\Module\RanglisteModule;
use Schachbulle\ContaoSchachaufgabenBundle\Module\TrainingModule;

// Backend-Modul im Bereich „Inhalte"
$GLOBALS['BE_MOD']['content']['schachaufgaben'] = array
(
	// Die Monatsranglisten sind über „Monatsranglisten" in der Aufgabenliste erreichbar
	'tables' => array('tl_schachaufgaben', 'tl_schachaufgaben_ranglistenstand'),
	// Import der Lichess-Sammlung (do=schachaufgaben&key=import)
	'import' => array(ImportSeite::class, 'ausfuehren'),
);

// Frontend-Module in eigener Gruppe
$GLOBALS['FE_MOD']['schachaufgaben'] = array
(
	'schachaufgaben_training'     => TrainingModule::class,
	'schachaufgaben_rangliste'    => RanglisteModule::class,
	'schachaufgaben_bestenliste'  => BestenlisteModule::class,
);

$GLOBALS['TL_MODELS']['tl_schachaufgaben'] = SchachaufgabeModel::class;
