<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * Tabelle tl_schachaufgaben_ranglistenstand: die Rangliste zum Monatsersten.
 *
 * Der Cronjob MonatsranglisteCron schreibt am Ersten jedes Monats eine Zeile je
 * Mitglied mit mindestens einer gespielten Aufgabe. Name und Zahlen werden als
 * Momentaufnahme gespeichert, damit spätere Änderungen am Mitglied die
 * Geschichte nicht verfälschen. Beim Löschen eines Mitglieds verschwinden auch
 * seine Einträge hier (MitgliedLoeschenListener).
 *
 * Im Backend nur lesbar (Inhalte → Schachaufgaben → Monatsranglisten).
 */
$GLOBALS['TL_DCA']['tl_schachaufgaben_ranglistenstand'] = array
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
				'id'                => 'primary',
				// Je Stichtag höchstens ein Eintrag je Mitglied; sichert auch gegen doppelte Cronläufe
				'stichtag,memberId' => 'unique',
				'memberId'          => 'index',
			)
		)
	),

	'list' => array
	(
		'sorting' => array
		(
			'mode'        => DataContainer::MODE_SORTABLE,
			'fields'      => array('stichtag DESC', 'platz'),
			'panelLayout' => 'filter;search,limit',
		),
		'label' => array
		(
			'fields'      => array('stichtag', 'platz', 'name', 'wertung', 'versuche', 'geloest'),
			'showColumns' => true,
		),
		'global_operations' => array
		(
			'aufgaben' => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['aufgaben'],
				'href'  => 'table=tl_schachaufgaben',
				'class' => 'header_back',
				'icon'  => 'back.svg',
			),
		),
		'operations' => array
		(
			'delete' => array
			(
				'label'      => &$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['delete'],
				'href'       => 'act=delete',
				'icon'       => 'delete.svg',
				'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
			),
			'show' => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['show'],
				'href'  => 'act=show',
				'icon'  => 'show.svg',
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
		// Monatserster 0 Uhr (Serverzeit), für den der Stand gilt
		'stichtag' => array
		(
			'filter' => true,
			'flag'   => DataContainer::SORT_DAY_DESC,
			'eval'   => array('rgxp' => 'date'),
			'sql'    => "int(10) unsigned NOT NULL default '0'",
		),
		'memberId' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'platz' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		// Name zum Stichtag als „Vorname N."
		'name' => array
		(
			'search' => true,
			'sql'    => "varchar(255) NOT NULL default ''",
		),
		'wertung' => array
		(
			'sql' => "smallint(5) unsigned NOT NULL default '0'",
		),
		'wertungAbweichung' => array
		(
			'sql' => "smallint(5) unsigned NOT NULL default '0'",
		),
		'versuche' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'geloest' => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'",
		),
		'bestWertung' => array
		(
			'sql' => "smallint(5) unsigned NOT NULL default '0'",
		)
	)
);
