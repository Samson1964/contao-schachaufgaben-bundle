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
 * Tabelle tl_schachaufgaben: eine Zeile je Aufgabe.
 *
 * Der Aufbau folgt der Aufgabensammlung von Lichess, damit deren CSV ohne
 * Umrechnung übernommen werden kann:
 *
 * - fen:   Stellung VOR dem ersten Zug. Dieser erste Zug in „zuege" ist der
 *          Zug des Gegners, der die Aufgabe auslöst; erst danach ist der
 *          Spieler am Zug.
 * - zuege: Alle Züge in UCI-Schreibweise (e2e4, e7e8q), durch Leerzeichen
 *          getrennt, abwechselnd Gegner und Spieler.
 *
 * Eigene Aufgaben (PGN-Import, manuelle Eingabe) werden in dieselbe Form
 * gebracht, damit das Frontend nur einen Fall kennen muss.
 */
$GLOBALS['TL_DCA']['tl_schachaufgaben'] = array
(
	'config' => array
	(
		'dataContainer'    => DC_Table::class,
		'enableVersioning' => true,
		'sql'              => array
		(
			'keys' => array
			(
				'id'                => 'primary',
				// Verhindert Doppelimporte; mehrere NULL-Werte (eigene Aufgaben) sind erlaubt
				'lichessId'         => 'unique',
				// Dasselbe für den PGN-Import: Prüfsumme aus FEN und Zügen
				'schluessel'        => 'unique',
				// Für die Suche nach einer passenden Aufgabe zur Wertung des Spielers
				'published,wertung' => 'index',
			)
		)
	),

	'list' => array
	(
		'sorting' => array
		(
			'mode'        => DataContainer::MODE_SORTABLE,
			'fields'      => array('wertung'),
			'flag'        => DataContainer::SORT_ASC,
			'panelLayout' => 'filter;sort,search,limit',
		),
		'label' => array
		(
			'fields'      => array('lichessId', 'wertung', 'spiele', 'motive'),
			'showColumns' => true,
		),
		'global_operations' => array
		(
			'ranglisten' => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_schachaufgaben']['ranglisten'],
				'href'  => 'table=tl_schachaufgaben_ranglistenstand',
				'class' => 'header_css_import',
				'icon'  => 'sync.svg',
			),
			'pgn' => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_schachaufgaben']['pgn'],
				'href'  => 'key=pgn',
				'class' => 'header_theme_import',
				'icon'  => 'theme_import.svg',
			),
			'import' => array
			(
				'label'      => &$GLOBALS['TL_LANG']['tl_schachaufgaben']['import'],
				'href'       => 'key=import',
				'class'      => 'header_theme_import',
				'icon'       => 'theme_import.svg',
			),
			'all' => array
			(
				'href'       => 'act=select',
				'class'      => 'header_edit_all',
				'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_schachaufgaben']['edit'],
				'href'  => 'act=edit',
				'icon'  => 'edit.svg',
			),
			'copy' => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_schachaufgaben']['copy'],
				'href'  => 'act=copy',
				'icon'  => 'copy.svg',
			),
			'delete' => array
			(
				'label'      => &$GLOBALS['TL_LANG']['tl_schachaufgaben']['delete'],
				'href'       => 'act=delete',
				'icon'       => 'delete.svg',
				'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
			),
			'toggle' => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_schachaufgaben']['toggle'],
				'href'  => 'act=toggle&amp;field=published',
				'icon'  => 'visible.svg',
			),
			'show' => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_schachaufgaben']['show'],
				'href'  => 'act=show',
				'icon'  => 'show.svg',
			)
		)
	),

	'palettes' => array
	(
		'default' => '{aufgabe_legend},fen,zuege,spielerZuerst,motive;{wertung_legend},wertung,wertungAbweichung,wertungVolatilitaet;{statistik_legend},spiele,beliebtheit,gefaellt,gefaelltNicht;{quelle_legend},quelle,lichessId,partieUrl,eroeffnung;{publish_legend},published',
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
		'fen' => array
		(
			'exclude'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'maxlength' => 100, 'decodeEntities' => true, 'tl_class' => 'long'),
			'sql'       => "varchar(100) NOT NULL default ''",
		),
		'zuege' => array
		(
			'exclude'   => true,
			'inputType' => 'text',
			'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'long'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		// Leer: Lichess-Format, der erste Zug ist der Gegnerzug, der die Aufgabe
		// auslöst. '1': Der Spieler ist in der FEN schon am Zug (etwa PGN-Aufgaben).
		'spielerZuerst' => array
		(
			'exclude'   => true,
			'filter'    => true,
			'inputType' => 'checkbox',
			'eval'      => array('tl_class' => 'clr'),
			'sql'       => "char(1) NOT NULL default ''",
		),
		// sha1 aus FEN und Zügen, nur bei PGN-Importen gesetzt; verhindert Dubletten
		'schluessel' => array
		(
			'eval' => array('doNotCopy' => true),
			'sql'  => 'varchar(40) NULL',
		),
		'motive' => array
		(
			'exclude'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('maxlength' => 255, 'tl_class' => 'long'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		// Glicko-2: Wertungszahl, Abweichung (Unsicherheit) und Volatilität.
		// Startwerte wie bei Lichess für neue Aufgaben.
		'wertung' => array
		(
			'exclude'   => true,
			'sorting'   => true,
			'flag'      => DataContainer::SORT_ASC,
			'inputType' => 'text',
			'eval'      => array('rgxp' => 'natural', 'maxlength' => 4, 'tl_class' => 'w50'),
			'sql'       => "smallint(5) unsigned NOT NULL default '1500'",
		),
		'wertungAbweichung' => array
		(
			'exclude'   => true,
			'inputType' => 'text',
			'eval'      => array('rgxp' => 'natural', 'maxlength' => 3, 'tl_class' => 'w50'),
			'sql'       => "smallint(5) unsigned NOT NULL default '350'",
		),
		'wertungVolatilitaet' => array
		(
			'exclude'   => true,
			'inputType' => 'text',
			'eval'      => array('rgxp' => 'digit', 'maxlength' => 10, 'tl_class' => 'w50'),
			'sql'       => "double NOT NULL default '0.06'",
		),
		// Eigene Statistik der Website; die Werte von Lichess dienen nur als
		// Filter beim Import und werden nicht gespeichert. Die Felder führt das
		// Training selbst, im Backend sind sie deshalb nur lesbar.
		//
		// Beliebtheit wie bei Lichess: 100 × (gefällt − gefällt nicht) / Stimmen,
		// also von -100 (alle dagegen) bis 100 (alle dafür), unabhängig davon,
		// wie viele abgestimmt haben.
		'beliebtheit' => array
		(
			'exclude'   => true,
			'sorting'   => true,
			'flag'      => DataContainer::SORT_ASC,
			'inputType' => 'text',
			'eval'      => array('readonly' => true, 'tl_class' => 'w50'),
			'sql'       => "smallint(5) NOT NULL default '0'",
		),
		'gefaellt' => array
		(
			'exclude'   => true,
			'inputType' => 'text',
			'eval'      => array('readonly' => true, 'tl_class' => 'w50'),
			'sql'       => "int(10) unsigned NOT NULL default '0'",
		),
		'gefaelltNicht' => array
		(
			'exclude'   => true,
			'inputType' => 'text',
			'eval'      => array('readonly' => true, 'tl_class' => 'w50'),
			'sql'       => "int(10) unsigned NOT NULL default '0'",
		),
		// Lösungsversuche auf dieser Website, von Mitgliedern und Gästen
		'spiele' => array
		(
			'exclude'   => true,
			'sorting'   => true,
			'flag'      => DataContainer::SORT_ASC,
			'inputType' => 'text',
			'eval'      => array('readonly' => true, 'tl_class' => 'w50'),
			'sql'       => "int(10) unsigned NOT NULL default '0'",
		),
		'quelle' => array
		(
			'exclude'   => true,
			'filter'    => true,
			'inputType' => 'select',
			'options'   => array('manuell', 'lichess', 'pgn'),
			'reference' => &$GLOBALS['TL_LANG']['tl_schachaufgaben']['quelle_optionen'],
			'eval'      => array('tl_class' => 'w50'),
			'sql'       => "varchar(16) NOT NULL default 'manuell'",
		),
		// Lichess-Kennungen unterscheiden Groß- und Kleinschreibung (00sHx ≠ 00shx),
		// deshalb binäre Sortierfolge. NULL bei eigenen Aufgaben, damit der
		// eindeutige Schlüssel sie nicht als Dubletten ablehnt.
		'lichessId' => array
		(
			'exclude'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('maxlength' => 16, 'nullIfEmpty' => true, 'doNotCopy' => true, 'tl_class' => 'w50'),
			'sql'       => 'varchar(16) BINARY NULL',
		),
		'partieUrl' => array
		(
			'exclude'   => true,
			'inputType' => 'text',
			'eval'      => array('rgxp' => 'url', 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'eroeffnung' => array
		(
			'exclude'   => true,
			'search'    => true,
			'inputType' => 'text',
			'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
			'sql'       => "varchar(255) NOT NULL default ''",
		),
		'published' => array
		(
			'exclude'   => true,
			'toggle'    => true,
			'filter'    => true,
			'inputType' => 'checkbox',
			'eval'      => array('doNotCopy' => true),
			'sql'       => "char(1) NOT NULL default ''",
		)
	)
);
