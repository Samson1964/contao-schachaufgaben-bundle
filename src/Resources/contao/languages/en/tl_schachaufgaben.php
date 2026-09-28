<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

// Legenden
$GLOBALS['TL_LANG']['tl_schachaufgaben']['aufgabe_legend'] = 'Puzzle';
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertung_legend'] = 'Rating';
$GLOBALS['TL_LANG']['tl_schachaufgaben']['statistik_legend'] = 'Statistics of this website';
$GLOBALS['TL_LANG']['tl_schachaufgaben']['quelle_legend'] = 'Source';
$GLOBALS['TL_LANG']['tl_schachaufgaben']['publish_legend'] = 'Publishing';

// Felder
$GLOBALS['TL_LANG']['tl_schachaufgaben']['fen'] = array('Position (FEN)', 'Position before the first move. The first move is the opponent’s; the player moves after it.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['zuege'] = array('Moves (UCI)', 'All moves in UCI notation, separated by spaces, e.g. "e8d7 a2e6 d7d8 f7f8". The first move is the opponent’s move that starts the puzzle.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['motive'] = array('Themes', 'Themes in Lichess format, separated by spaces, e.g. "fork middlegame short".');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertung'] = array('Rating', 'Difficulty of the puzzle as a Glicko-2 rating.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertungAbweichung'] = array('Rating deviation', 'Uncertainty of the rating (Glicko-2 RD). The smaller, the more reliable.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['wertungVolatilitaet'] = array('Volatility', 'Glicko-2 volatility. Only change it if you know what you are doing.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['beliebtheit'] = array('Popularity', '100 × (likes − dislikes) ÷ votes, i.e. from -100 (everybody against) to 100 (everybody in favour). Calculated by the training.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['gefaellt'] = array('Likes', 'Number of "like" votes.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['gefaelltNicht'] = array('Dislikes', 'Number of "dislike" votes.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['spiele'] = array('Attempts', 'How often the puzzle was played on this website, by members and guests.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['quelle'] = array('Source', 'Where the puzzle comes from.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['lichessId'] = array('Lichess ID', 'ID of the puzzle on Lichess. Empty for your own puzzles.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['partieUrl'] = array('Source game', 'Address of the game the puzzle was taken from.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['eroeffnung'] = array('Opening', 'Opening of the source game.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['published'] = array('Published', 'The puzzle is offered in the training.');

// Auswahlwerte
$GLOBALS['TL_LANG']['tl_schachaufgaben']['quelle_optionen'] = array
(
	'manuell' => 'Entered manually',
	'lichess' => 'Lichess puzzle database',
	'pgn'     => 'PGN import',
);

// Operationen
$GLOBALS['TL_LANG']['tl_schachaufgaben']['ranglisten'] = array('Monthly rankings', 'View the rankings saved on the first of each month');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['import'] = array('Lichess import', 'Import puzzles from the Lichess puzzle database');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['new'] = array('New puzzle', 'Create a new puzzle');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['edit'] = array('Edit puzzle', 'Edit puzzle ID %s');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['copy'] = array('Duplicate puzzle', 'Duplicate puzzle ID %s');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['delete'] = array('Delete puzzle', 'Delete puzzle ID %s');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['toggle'] = array('Publish/unpublish puzzle', 'Publish/unpublish puzzle ID %s');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['show'] = array('Puzzle details', 'Show the details of puzzle ID %s');

// Importseite (do=schachaufgaben&key=import)
$GLOBALS['TL_LANG']['tl_schachaufgaben']['import_seite'] = array
(
	'zurueck'            => 'Go back',
	'ueberschrift'       => 'Import puzzles from the Lichess database',
	'anleitung'          => 'The puzzle database is available in the public domain (CC0) at <a href="https://database.lichess.org/#puzzles" target="_blank" rel="noopener">database.lichess.org</a>. Unpack <code>lichess_db_puzzle.csv.zst</code> on your computer (e.g. with <code>zstd -d</code> or 7-Zip ZS) and upload the CSV file via FTP to <code>%s</code> or a subfolder. The import runs in chunks of a few seconds; keep this window open meanwhile.',
	'keineDateien'       => 'There is no CSV file in <code>%s</code>.',
	'datei'              => 'CSV file',
	'filter_legend'      => 'Selection',
	'minBeliebtheit'     => array('Lichess popularity from', 'From -100 to 100, 80 recommended. Only used for selection.'),
	'minSpiele'          => array('Played on Lichess from', '1000 recommended. Only used for selection.'),
	'minWertung'         => array('Rating from', 'Leave empty for no lower limit.'),
	'maxWertung'         => array('Rating up to', 'Leave empty for no upper limit.'),
	'motive'             => array('Themes', 'Lichess names, e.g. "fork mateIn2". Empty = all.'),
	'limit'              => array('At most', 'Import this many puzzles. Empty = all.'),
	'optionen_legend'    => 'Existing puzzles',
	'aktualisieren'      => array('Update existing puzzles', 'Take over position, moves, themes, opening and source game again. Otherwise existing puzzles are skipped.'),
	'wertungUebernehmen' => array('Also take over the rating', 'Overwrites the puzzle ratings built up in your own training.'),
	'unveroeffentlicht'  => array('Do not publish new puzzles', 'The puzzles only appear in the training once they are published.'),
	'starten'            => 'Start import',
	'fortsetzen'         => 'Continue',
	'verwerfen'          => 'Discard',
	'anhalten'           => 'Pause',
	'offen'              => 'An import of <code>%s</code> was interrupted at %s %%.',
	'laeuft'             => 'Importing …',
	'angehalten'         => 'Paused. "Continue" resumes at the same place.',
	'fertig'             => 'Import finished.',
	'gelesen'            => 'lines read',
	'fehlerhaft'         => 'invalid',
	'gefiltert'          => 'filtered out',
	'uebernommen'        => 'accepted',
	'geschrieben'        => 'changed in the database',
	'dauer'              => 'Duration',
	'fehlerbeispiele'    => 'Invalid lines (excerpt)',
	'serverfehler'       => 'The server responded with an error',
);

// Feld „Spieler zieht zuerst" und PGN-Import (do=schachaufgaben&key=pgn)
$GLOBALS['TL_LANG']['tl_schachaufgaben']['spielerZuerst'] = array('Player moves first', 'The player is already to move in the position; there is no opponent move starting the puzzle (common for puzzles from PGN collections). The number of moves is then odd.');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['pgn'] = array('PGN import', 'Import your own puzzles from a PGN file');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['pgn_seite'] = array
(
	'zurueck'          => 'Go back',
	'anleitung'        => 'Every game in the PGN file becomes a puzzle: starting position from the <code>[FEN]</code> tag, solution from the main line; variations and comments are skipped. Optionally <code>[Rating]</code> is used as the rating and <code>[Themes]</code> as the themes. The file is read in the browser and not uploaded; puzzles that already exist are skipped.',
	'datei'            => 'PGN file',
	'optionen_legend'  => 'Defaults',
	'spielerZuerst'    => array('Player moves first', 'The solver is to move in the position (common for puzzle collections). Uncheck if the first move is the opponent’s, as on Lichess.'),
	'wertung'          => array('Rating', 'For puzzles without [Rating]; adapts through the training.'),
	'motive'           => array('Themes', 'For puzzles without [Themes], e.g. "fork pin".'),
	'unveroeffentlicht' => array('Do not publish new puzzles', 'The puzzles only appear in the training once they are published.'),
	'pruefen'          => 'Check file',
	'speichern'        => 'Save puzzles',
	'partie'           => 'Game %d',
	'geprueft'         => 'File checked, nothing saved yet.',
	'vorschau'         => '%d games, %d usable puzzles and %d invalid.',
	'speichert'        => 'Saving puzzles …',
	'ergebnis'         => '%d newly saved, %d already existing, %d rejected by the server.',
	'fertig'           => 'Import finished.',
	'fehlerbeispiele'  => 'Not imported (excerpt):',
	'serverfehler'     => 'The server responded with an error',
);

// Statistik (do=schachaufgaben&key=statistik)
$GLOBALS['TL_LANG']['tl_schachaufgaben']['statistik'] = array('Statistics', 'Analyse page views and puzzles played');
$GLOBALS['TL_LANG']['tl_schachaufgaben']['statistik_seite'] = array
(
	'zurueckModul'    => 'Go back',
	'ueberschrift'    => 'Statistics of views and puzzles played',
	'zeitraumTitel'   => 'Period',
	'ebene_tag'       => 'Day',
	'ebene_monat'     => 'Month',
	'ebene_jahr'      => 'Year',
	'zurueck'         => 'back',
	'vor'             => 'forward',
	'heute'           => 'until today',
	'bestand'         => '%s published puzzles · %s members with a rating',
	'keineDaten'      => 'Nothing was counted for this period. Use "back" to go to an earlier period; the buttons above turn the day into a whole month or year.',
	'art_aufruf'      => 'puzzles viewed',
	'art_begonnen'    => 'puzzles played',
	'art_geloest'     => 'solved',
	'art_nichtgeloest' => 'not solved',
	'davon'           => '%s members · %s guests',
	'quote'           => 'Solving rate',
	'neueSpieler'     => '%s new members in this period',
	'diagrammAufrufe' => 'Views and puzzles played',
	'diagrammGeloest' => 'Puzzles played and solved',
	'legendeAufrufe'  => array('played', 'viewed'),
	'legendeGeloest'  => array('solved', 'played'),
	'meistgespielt'   => 'Most played puzzles (members)',
	'aktivste'        => 'Most active members',
	'keineMitglieder' => 'No members played in this period.',
	'platz'           => 'Rank',
	'gespielt'        => 'Played',
	'geloestSpalte'   => 'Solved',
	'aufgabe'         => 'Puzzle',
	'wertung'         => 'Rating',
	'beliebtheit'     => 'Popularity',
	'name'            => 'Name',
	'aktuelleWertung' => 'Current rating',
	'hinweis'         => 'Every puzzle shown counts as viewed; every puzzle with at least one move or "show solution" counts as played. Counting starts with version 1.4.0. The rankings are based on the members’ attempts; guests are not included there.',
);
