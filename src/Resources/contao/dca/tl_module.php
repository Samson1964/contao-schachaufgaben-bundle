<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Paletten und Felder der beiden Frontend-Module.
 *
 * Verwendet werden nur Kernfelder, die es in Contao 4.13 und 5.7 gleichermaßen
 * gibt; „guests" und „space" fehlen in Contao 5 und würden die Palette dort
 * abbrechen lassen.
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['schachaufgaben_training']
	= '{title_legend},name,headline,type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['schachaufgaben_rangliste']
	= '{title_legend},name,headline,type;{config_legend},numberOfItems,schachaufgabenMinVersuche;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['schachaufgabenMinVersuche'] = array
(
	'exclude'   => true,
	'inputType' => 'text',
	'eval'      => array('rgxp' => 'natural', 'maxlength' => 5, 'tl_class' => 'w50'),
	'sql'       => "smallint(5) unsigned NOT NULL default '20'",
);
