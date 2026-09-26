<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DC_Table;

/*
 * Tabelle tl_schachaufgaben_spieler: Glicko-2-Wertung und Zähler je Mitglied.
 *
 * Gäste stehen nicht hier; ihre Wertung lebt nur in der Sitzung. Die Tabelle
 * wird ausschließlich vom Training beschrieben und hat keine Backend-Maske.
 * Die Wertung wird ungerundet gespeichert, damit sich Rundungsfehler über
 * viele Versuche nicht aufsummieren.
 */
$GLOBALS['TL_DCA']['tl_schachaufgaben_spieler'] = array
(
	'config' => array
	(
		'dataContainer' => DC_Table::class,
		'closed'        => true,
		'notEditable'   => true,
		'notCopyable'   => true,
		'sql'           => array
		(
			'keys' => array
			(
				'id'       => 'primary',
				'memberId' => 'unique',
				// Für die Rangliste
				'versuche,wertung' => 'index',
			)
		)
	),

	'fields' => array
	(
		'id' => array
		(
			'sql' => 'int(10) unsigned NOT NULL auto_increment',
		),
		'tstamp' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'memberId' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'wertung' => array
		(
			'sql' => "double NOT NULL default '1500'",
		),
		'wertungAbweichung' => array
		(
			'sql' => "double NOT NULL default '350'",
		),
		'wertungVolatilitaet' => array
		(
			'sql' => "double NOT NULL default '0.06'",
		),
		'versuche' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'geloest' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		)
	)
);
