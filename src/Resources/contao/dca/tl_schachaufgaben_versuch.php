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
 * Tabelle tl_schachaufgaben_versuch: ein Eintrag je Aufgabe, die einem
 * Mitglied gestellt wurde.
 *
 * Der Eintrag entsteht schon beim Stellen der Aufgabe (gewertet = ''), nicht
 * erst beim Lösen. So wird eine Aufgabe einem Mitglied nie ein zweites Mal
 * vorgelegt, auch nicht nach Neuladen der Seite oder Überspringen. Der
 * eindeutige Schlüssel (memberId, aufgabe) sichert das in der Datenbank ab
 * und dient zugleich der schnellen Ausschlussprüfung bei der Aufgabenwahl.
 * Mit dem Ergebnis wird der Eintrag vervollständigt. Keine Backend-Maske.
 */
$GLOBALS['TL_DCA']['tl_schachaufgaben_versuch'] = array
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
				'id'               => 'primary',
				'memberId,aufgabe' => 'unique',
				'aufgabe'          => 'index',
			)
		)
	),

	'fields' => array
	(
		'id' => array
		(
			'sql' => 'int(10) unsigned NOT NULL auto_increment',
		),
		// Zeitpunkt, zu dem die Aufgabe gestellt wurde
		'tstamp' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'memberId' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		// ID aus tl_schachaufgaben
		'aufgabe' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		// '1' sobald das Ergebnis eingegangen und gewertet ist
		'gewertet' => array
		(
			'sql' => "char(1) NOT NULL default ''",
		),
		'geloest' => array
		(
			'sql' => "char(1) NOT NULL default ''",
		),
		'wertungVorher' => array
		(
			'sql' => "smallint(5) unsigned NOT NULL default '0'",
		),
		'wertungNachher' => array
		(
			'sql' => "smallint(5) unsigned NOT NULL default '0'",
		)
	)
);
