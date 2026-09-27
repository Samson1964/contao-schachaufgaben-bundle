<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

// Felder
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['stichtag'] = array('Stichtag', 'Monatserster, zu dem der Stand gespeichert wurde.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['platz'] = array('Platz', 'Platz nach der Wertung zum Stichtag.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['name'] = array('Name', 'Name zum Stichtag als „Vorname N.“.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['wertung'] = array('Wertung', 'Wertung zum Stichtag.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['wertungAbweichung'] = array('Abweichung', 'Unsicherheit der Wertung zum Stichtag.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['versuche'] = array('Aufgaben', 'Bis zum Stichtag gespielte Aufgaben.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['geloest'] = array('Gelöst', 'Bis zum Stichtag gelöste Aufgaben.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['bestWertung'] = array('Beste Wertung', 'Höchste gesicherte Wertung bis zum Stichtag.');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['memberId'] = array('Mitglied', 'ID des Mitglieds.');

// Operationen
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['aufgaben'] = array('Zu den Aufgaben', 'Zurück zur Liste der Aufgaben');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['delete'] = array('Eintrag löschen', 'Eintrag ID %s löschen');
$GLOBALS['TL_LANG']['tl_schachaufgaben_ranglistenstand']['show'] = array('Details', 'Details des Eintrags ID %s anzeigen');
