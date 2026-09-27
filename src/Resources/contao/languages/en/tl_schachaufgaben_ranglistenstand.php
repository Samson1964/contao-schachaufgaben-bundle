<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

// Felder
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['stichtag'] = array('Reference date', 'First of the month on which the ranking was saved.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['platz'] = array('Rank', 'Rank by rating on the reference date.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['name'] = array('Name', 'Name on the reference date as "First name L.".');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['wertung'] = array('Rating', 'Rating on the reference date.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['wertungAbweichung'] = array('Rating deviation', 'Uncertainty of the rating on the reference date.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['versuche'] = array('Puzzles', 'Puzzles played up to the reference date.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['geloest'] = array('Solved', 'Puzzles solved up to the reference date.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['bestWertung'] = array('Best rating', 'Highest confirmed rating up to the reference date.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['memberId'] = array('Member', 'ID of the member.');

// Operationen
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['aufgaben'] = array('To the puzzles', 'Back to the list of puzzles');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['delete'] = array('Delete entry', 'Delete entry ID %s');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['show'] = array('Details', 'Show the details of entry ID %s');
