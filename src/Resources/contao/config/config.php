<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

use Schachbulle\ContaoSchachaufgabenBundle\Model\SchachaufgabeModel;

// Backend-Modul im Bereich „Inhalte"
$GLOBALS['BE_MOD']['content']['schachaufgaben'] = array
(
	'tables' => array('tl_schachaufgaben'),
);

$GLOBALS['TL_MODELS']['tl_schachaufgaben'] = SchachaufgabeModel::class;
