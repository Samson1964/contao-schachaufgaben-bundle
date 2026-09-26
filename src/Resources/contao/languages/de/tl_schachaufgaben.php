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
$GLOBALS['TL_LANG']['tl_schachaufgaben']['new'] = array('Neue Aufgabe', 'Eine neue Aufgabe anlegen');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['edit'] = array('Aufgabe bearbeiten', 'Aufgabe ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['copy'] = array('Aufgabe duplizieren', 'Aufgabe ID %s duplizieren');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['delete'] = array('Aufgabe löschen', 'Aufgabe ID %s löschen');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['toggle'] = array('Aufgabe veröffentlichen/verbergen', 'Aufgabe ID %s veröffentlichen/verbergen');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['show'] = array('Aufgabendetails', 'Details der Aufgabe ID %s anzeigen');
