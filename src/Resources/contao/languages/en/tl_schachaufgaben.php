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
