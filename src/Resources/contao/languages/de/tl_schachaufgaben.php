<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

// Legenden
$GLOBALS['TL_LANG']['tl_schachaufgaben']['aufgabe_legend'] = 'Aufgabe';
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertung_legend'] = 'Wertung';
$GLOBALS['TL_LANG']['tl_schachaufgaben']['quelle_legend'] = 'Herkunft';
$GLOBALS['TL_LANG']['tl_schachaufgaben']['publish_legend'] = 'Veröffentlichung';

// Felder
$GLOBALS['TL_LANG']['tl_schachaufgaben']['fen'] = array('Stellung (FEN)', 'Stellung vor dem ersten Zug. Der erste Zug ist der des Gegners, danach ist der Spieler am Zug.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['zuege'] = array('Züge (UCI)', 'Alle Züge in UCI-Schreibweise, durch Leerzeichen getrennt, z. B. „e8d7 a2e6 d7d8 f7f8". Der erste Zug ist der Gegnerzug, der die Aufgabe auslöst.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['motive'] = array('Motive', 'Motive im Lichess-Format, durch Leerzeichen getrennt, z. B. „fork middlegame short".');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertung'] = array('Wertungszahl', 'Schwierigkeit der Aufgabe als Glicko-2-Wertungszahl.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertungAbweichung'] = array('Abweichung', 'Unsicherheit der Wertungszahl (Glicko-2-RD). Je kleiner, desto verlässlicher.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertungVolatilitaet'] = array('Volatilität', 'Glicko-2-Volatilität. Nur ändern, wenn Sie wissen, was Sie tun.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['beliebtheit'] = array('Beliebtheit', 'Bewertung durch die Spieler von -100 (unbeliebt) bis 100 (beliebt).');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['spiele'] = array('Spiele', 'Wie oft die Aufgabe bereits gespielt wurde.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['quelle'] = array('Quelle', 'Woher die Aufgabe stammt.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['lichessId'] = array('Lichess-Kennung', 'Kennung der Aufgabe bei Lichess. Leer bei eigenen Aufgaben.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['partieUrl'] = array('Herkunftspartie', 'Adresse der Partie, aus der die Aufgabe stammt.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['eroeffnung'] = array('Eröffnung', 'Eröffnung der Herkunftspartie.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['published'] = array('Veröffentlicht', 'Die Aufgabe wird im Training angeboten.');

// Auswahlwerte
$GLOBALS['TL_LANG']['tl_schachaufgaben']['quelle_optionen'] = array
(
	'manuell' => 'Manuell eingegeben',
	'lichess' => 'Lichess-Aufgabensammlung',
	'pgn'     => 'PGN-Import',
);

// Operationen
$GLOBALS['TL_LANG']['tl_schachaufgaben']['import'] = array('Lichess-Import', 'Aufgaben aus der Lichess-Aufgabensammlung importieren');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['new'] = array('Neue Aufgabe', 'Eine neue Aufgabe anlegen');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['edit'] = array('Aufgabe bearbeiten', 'Aufgabe ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['copy'] = array('Aufgabe duplizieren', 'Aufgabe ID %s duplizieren');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['delete'] = array('Aufgabe löschen', 'Aufgabe ID %s löschen');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['toggle'] = array('Aufgabe veröffentlichen/verbergen', 'Aufgabe ID %s veröffentlichen/verbergen');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['show'] = array('Aufgabendetails', 'Details der Aufgabe ID %s anzeigen');

// Importseite (do=schachaufgaben&key=import)
$GLOBALS['TL_LANG']['tl_schachaufgaben']['import_seite'] = array
(
	'zurueck'            => 'Zurück',
	'ueberschrift'       => 'Aufgaben aus der Lichess-Sammlung importieren',
	'anleitung'          => 'Die Aufgabensammlung gibt es gemeinfrei (CC0) unter <a href="https://database.lichess.org/#puzzles" target="_blank" rel="noopener">database.lichess.org</a>. Die Datei <code>lichess_db_puzzle.csv.zst</code> auf dem eigenen Rechner entpacken (etwa mit <code>zstd -d</code> oder 7-Zip ZS) und die CSV-Datei per FTP in das Verzeichnis <code>%s</code> oder einen Unterordner hochladen. Der Import läuft in Häppchen von einigen Sekunden; das Fenster muss dabei geöffnet bleiben.',
	'keineDateien'       => 'In <code>%s</code> liegt keine CSV-Datei.',
	'datei'              => 'CSV-Datei',
	'filter_legend'      => 'Auswahl',
	'minBeliebtheit'     => array('Mindest-Beliebtheit', 'Von -100 bis 100. Empfohlen: 80.'),
	'minSpiele'          => array('Mindestens gespielt', 'Wie oft die Aufgabe bei Lichess mindestens gespielt wurde. Empfohlen: 1000.'),
	'minWertung'         => array('Wertung ab', 'Leer lassen für keine Untergrenze.'),
	'maxWertung'         => array('Wertung bis', 'Leer lassen für keine Obergrenze.'),
	'motive'             => array('Motive', 'Lichess-Namen, z. B. „fork mateIn2". Leer = alle.'),
	'limit'              => array('Höchstens', 'So viele Aufgaben übernehmen. Leer = alle.'),
	'optionen_legend'    => 'Vorhandene Aufgaben',
	'aktualisieren'      => array('Vorhandene Aufgaben aktualisieren', 'Stellung, Züge, Motive und Lichess-Zähler neu übernehmen. Sonst werden vorhandene Aufgaben übersprungen.'),
	'wertungUebernehmen' => array('Dabei auch die Wertung übernehmen', 'Überschreibt die im eigenen Training entstandene Wertung der Aufgaben.'),
	'unveroeffentlicht'  => array('Neue Aufgaben nicht veröffentlichen', 'Die Aufgaben erscheinen erst im Training, wenn sie freigeschaltet sind.'),
	'starten'            => 'Import starten',
	'fortsetzen'         => 'Fortsetzen',
	'verwerfen'          => 'Verwerfen',
	'anhalten'           => 'Anhalten',
	'offen'              => 'Ein Import von <code>%s</code> wurde bei %s %% unterbrochen.',
	'laeuft'             => 'Import läuft …',
	'angehalten'         => 'Angehalten. Mit „Fortsetzen" geht es an derselben Stelle weiter.',
	'fertig'             => 'Import beendet.',
	'gelesen'            => 'Zeilen gelesen',
	'fehlerhaft'         => 'fehlerhaft',
	'gefiltert'          => 'ausgefiltert',
	'uebernommen'        => 'übernommen',
	'geschrieben'        => 'in der Datenbank geändert',
	'dauer'              => 'Laufzeit',
	'fehlerbeispiele'    => 'Fehlerhafte Zeilen (Auszug)',
	'serverfehler'       => 'Der Server hat mit einem Fehler geantwortet',
);
