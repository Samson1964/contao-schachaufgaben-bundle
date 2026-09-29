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
$GLOBALS['TL_LANG']['tl_schachaufgaben']['statistik_legend'] = 'Statistik dieser Website';
$GLOBALS['TL_LANG']['tl_schachaufgaben']['quelle_legend'] = 'Herkunft';
$GLOBALS['TL_LANG']['tl_schachaufgaben']['publish_legend'] = 'Veröffentlichung';

// Felder
$GLOBALS['TL_LANG']['tl_schachaufgaben']['fen'] = array('Stellung (FEN)', 'Stellung vor dem ersten Zug. Der erste Zug ist der des Gegners, danach ist der Spieler am Zug.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['zuege'] = array('Züge (UCI)', 'Alle Züge in UCI-Schreibweise, durch Leerzeichen getrennt, z. B. „e8d7 a2e6 d7d8 f7f8". Der erste Zug ist der Gegnerzug, der die Aufgabe auslöst.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['motive'] = array('Motive', 'Motive im Lichess-Format, durch Leerzeichen getrennt, z. B. „fork middlegame short".');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertung'] = array('Wertungszahl', 'Schwierigkeit der Aufgabe als Glicko-2-Wertungszahl.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertungAbweichung'] = array('Abweichung', 'Unsicherheit der Wertungszahl (Glicko-2-RD). Je kleiner, desto verlässlicher.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertungVolatilitaet'] = array('Volatilität', 'Glicko-2-Volatilität. Nur ändern, wenn Sie wissen, was Sie tun.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['beliebtheit'] = array('Beliebtheit', '100 × (Gefällt mir − Gefällt mir nicht) ÷ Stimmen, also von -100 (alle dagegen) bis 100 (alle dafür). Wird vom Training berechnet.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['gefaellt'] = array('Gefällt mir', 'Anzahl der Stimmen „Gefällt mir“.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['gefaelltNicht'] = array('Gefällt mir nicht', 'Anzahl der Stimmen „Gefällt mir nicht“.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['spiele'] = array('Lösungsversuche', 'Wie oft die Aufgabe auf dieser Website gespielt wurde, von Mitgliedern und Gästen.');
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
$GLOBALS['TL_LANG']['tl_schachaufgaben']['ranglisten'] = array('Monatsranglisten', 'Die zum Monatsersten gespeicherten Ranglisten ansehen');
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
	'minBeliebtheit'     => array('Beliebtheit bei Lichess ab', 'Von -100 bis 100, empfohlen 80. Nur zur Auswahl.'),
	'minSpiele'          => array('Bei Lichess gespielt ab', 'Empfohlen 1000. Nur zur Auswahl.'),
	'minWertung'         => array('Wertung ab', 'Leer lassen für keine Untergrenze.'),
	'maxWertung'         => array('Wertung bis', 'Leer lassen für keine Obergrenze.'),
	'motive'             => array('Motive', 'Lichess-Namen, z. B. „fork mateIn2". Leer = alle.'),
	'limit'              => array('Höchstens', 'So viele Aufgaben übernehmen. Leer = alle.'),
	'optionen_legend'    => 'Vorhandene Aufgaben',
	'aktualisieren'      => array('Vorhandene Aufgaben aktualisieren', 'Stellung, Züge, Motive, Eröffnung und Herkunftspartie neu übernehmen. Sonst werden vorhandene Aufgaben übersprungen.'),
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

// Feld „Spieler zieht zuerst" und PGN-Import (do=schachaufgaben&key=pgn)
$GLOBALS['TL_LANG']['tl_schachaufgaben']['spielerZuerst'] = array('Spieler zieht zuerst', 'Der Spieler ist in der Stellung schon am Zug, es gibt keinen auslösenden Gegnerzug (üblich bei Aufgaben aus PGN-Sammlungen). Dann ist die Anzahl der Züge ungerade.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['pgn'] = array('PGN-Import', 'Eigene Aufgaben aus einer PGN-Datei importieren');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['pgn_seite'] = array
(
	'zurueck'          => 'Zurück',
	'anleitung'        => 'Jede Partie der PGN-Datei wird eine Aufgabe: Ausgangsstellung aus dem Tag <code>[FEN]</code>, Lösung aus der Hauptvariante; Varianten und Kommentare werden übergangen. Optional übernimmt der Import <code>[Rating]</code> als Wertung und <code>[Themes]</code> als Motive. Die Datei wird im Browser gelesen und nicht hochgeladen; Aufgaben, die es schon gibt, werden übersprungen.',
	'datei'            => 'PGN-Datei',
	'optionen_legend'  => 'Voreinstellungen',
	'spielerZuerst'    => array('Spieler zieht zuerst', 'In der Stellung ist der Löser am Zug (üblich bei Aufgabensammlungen). Abschalten, wenn der erste Zug der des Gegners ist, wie bei Lichess.'),
	'wertung'          => array('Wertung', 'Für Aufgaben ohne [Rating]; passt sich durch das Training an.'),
	'motive'           => array('Motive', 'Für Aufgaben ohne [Themes], z. B. „fork pin".'),
	'unveroeffentlicht' => array('Neue Aufgaben nicht veröffentlichen', 'Die Aufgaben erscheinen erst im Training, wenn sie freigeschaltet sind.'),
	'pruefen'          => 'Datei prüfen',
	'speichern'        => 'Aufgaben speichern',
	'partie'           => 'Partie %d',
	'geprueft'         => 'Datei geprüft, noch nichts gespeichert.',
	'vorschau'         => '%d Partien, davon %d brauchbare Aufgaben und %d fehlerhaft.',
	'speichert'        => 'Aufgaben werden gespeichert …',
	'ergebnis'         => '%d neu gespeichert, %d schon vorhanden, %d vom Server abgewiesen.',
	'fertig'           => 'Import beendet.',
	'fehlerbeispiele'  => 'Nicht übernommen (Auszug):',
	'serverfehler'     => 'Der Server hat mit einem Fehler geantwortet',
);

// Statistik (do=schachaufgaben&key=statistik)
$GLOBALS['TL_LANG']['tl_schachaufgaben']['statistik'] = array('Statistik', 'Zugriffe und gespielte Aufgaben auswerten');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['statistik_seite'] = array
(
	'zurueckModul'    => 'Zurück',
	'ueberschrift'    => 'Statistik der Zugriffe und gespielten Aufgaben',
	'zeitraumTitel'   => 'Zeitraum',
	'ebene_tag'       => 'Tag',
	'ebene_monat'     => 'Monat',
	'ebene_jahr'      => 'Jahr',
	'zurueck'         => 'zurück',
	'vor'             => 'vor',
	'heute'           => 'bis heute',
	'bestand'         => '%s veröffentlichte Aufgaben · %s Mitglieder mit Wertung',
	'bestandEins'     => '%s veröffentlichte Aufgaben · 1 Mitglied mit Wertung',
	'keineDaten'      => 'Für diesen Zeitraum ist nichts gezählt. Mit „zurück“ lässt sich ein früherer Zeitraum ansteuern; über die Knöpfe oben wird aus dem Tag ein ganzer Monat oder ein ganzes Jahr.',
	'art_aufruf'      => 'Aufgaben aufgerufen',
	'art_begonnen'    => 'Aufgaben gespielt',
	'art_geloest'     => 'gelöst',
	'art_nichtgeloest' => 'nicht gelöst',
	'davon'           => '%s Mitglieder · %s Gäste',
	'quote'           => 'Lösungsquote',
	'neueSpieler'     => '%s neue Mitglieder im Zeitraum',
	'neueSpielerEins' => '1 neues Mitglied im Zeitraum',
	'diagrammAufrufe' => 'Aufrufe und gespielte Aufgaben',
	'diagrammGeloest' => 'Gespielte und gelöste Aufgaben',
	'legendeAufrufe'  => array('gespielt', 'aufgerufen'),
	'legendeGeloest'  => array('gelöst', 'gespielt'),
	'meistgespielt'   => 'Meistgespielte Aufgaben (Mitglieder)',
	'aktivste'        => 'Aktivste Mitglieder',
	'keineMitglieder' => 'In diesem Zeitraum haben keine Mitglieder gespielt.',
	'platz'           => 'Platz',
	'gespielt'        => 'Gespielt',
	'geloestSpalte'   => 'Gelöst',
	'aufgabe'         => 'Aufgabe',
	'wertung'         => 'Wertung',
	'beliebtheit'     => 'Beliebtheit',
	'name'            => 'Name',
	'aktuelleWertung' => 'Aktuelle Wertung',
	'hinweis'         => 'Aufgerufen zählt jede gestellte Aufgabe, gespielt jede Aufgabe mit mindestens einem Zug oder „Lösung zeigen“. Die Zählung beginnt mit Fassung 1.4.0. Die Ranglisten beruhen auf den Versuchen der Mitglieder; Gäste werden dort nicht erfasst.',
);
