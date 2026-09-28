# Schachaufgaben-Bundle Changelog

## Version 1.2.1 (2026-09-28)

* Change: Das bloße Aufrufen einer Aufgabe speichert nichts mehr. Einträge in
  `tl_schachaufgaben_spieler` und `tl_schachaufgaben_versuch` (bei Gästen in der Merkliste)
  entstehen erst mit dem ersten Zug (neue Route `/_schachaufgaben/beginn`) oder mit
  „Lösung zeigen". Nur angesehene Aufgaben können dadurch später wieder gestellt werden.
  Die Sitzung merkt sich mehrere offene Aufgaben, damit sich zwei Tabs nicht stören.
* Fix: Die gezogene Figur verschwand während des Ziehens hinter Seitenbereichen des Themes;
  sie bekommt jetzt einen `z-index`.

## Version 1.2.0 (2026-09-27)

* Add: Höchste erreichte Wertung mit Datum und Datum der ersten Benutzung je Mitglied
  (`bestWertung`, `bestDatum`, `ersteNutzung` in `tl_schachaufgaben_spieler`). Als Bestwertung
  zählt nur eine gesicherte Wertung (Abweichung höchstens 110).
* Add: Frontend-Modul „Schachaufgaben-Bestenliste" (ewige Bestenliste) mit Datum und eigenem
  Platz unter der Tabelle.
* Add: Monatsranglisten: Ein Cronjob speichert zum Monatsersten die Rangliste in
  `tl_schachaufgaben_ranglistenstand`; Konsolenbefehl `schachaufgaben:rangliste-speichern`;
  Backend-Liste „Monatsranglisten".
* Add: Migration trägt für bestehende Mitglieder erste Nutzung und Bestwertung nach.
* Change: Beim Löschen eines Mitglieds verschwinden auch seine Einträge in den
  Monatsranglisten.

## Version 1.1.1 (2026-09-27)

* Fix: Nach einem Update lief im Browser weiter das alte Trainingsskript, weil Server Dateien
  unter `/bundles` oft ein Jahr lang zwischenspeichern lassen (bei 1.1.0 fehlten dadurch die
  Bewertungsknöpfe). `training.js` wird jetzt mit einer Versionsangabe aus dem
  Änderungsdatum eingebunden und lädt `eroeffnung.js` mit derselben Angabe nach.
* Fix: Deutlicherer Kontrast beim Überfahren der Knöpfe („Lösung zeigen", „Gefällt mir" …):
  dunkelblau mit weißer Schrift statt hellblau.
* Fix: Die Bewertungsknöpfe waren vor dem Ende einer Aufgabe nicht zuverlässig verborgen,
  weil `display: flex` das `hidden`-Attribut aufhob.

## Version 1.1.0 (2026-09-27)

* Change: PHP 8.1 ist Voraussetzung (bisher 7.4). Der Konsolenbefehl meldet sich über
  `#[AsCommand]` an, Callbacks und Hooks über `#[AsCallback]` / `#[AsHook]`.
* Change: Beliebtheit und Spielzahl von Lichess dienen nur noch als Filter beim Import und
  werden nicht mehr gespeichert. `spiele` zählt die Lösungsversuche auf der eigenen Website,
  `beliebtheit` ergibt sich aus den eigenen Stimmen (neue Felder `gefaellt`, `gefaelltNicht`).
  Eine Migration setzt die unter 1.0.0 übernommenen Lichess-Werte einmalig auf 0; sie legt
  die Spalte `gefaellt` selbst an, damit Contao sie nicht wiederholt ausführt.
* Add: Knöpfe „Gefällt mir" / „Gefällt mir nicht" nach dem Ende einer Aufgabe, mit Ändern und
  Zurücknehmen der Stimme; neue Route `/_schachaufgaben/bewertung`, Stimme je Mitglied in
  `tl_schachaufgaben_versuch.stimme`, bei Gästen in der Sitzung.
* Add: Deutsche Namen der Eröffnungen im Training (`eroeffnung.js` mit Wörterbuch für alle
  156 Familien der Sammlung, Variantennamen, Beugung der Beiwörter und deutsche
  Figurenbuchstaben); gezeigt wird der genaueste Name.
* Add: Englische Sprachdateien.
* Add: Rangliste zeigt das angemeldete Mitglied auch außerhalb der gezeigten Plätze unter der
  Tabelle, vor Erreichen der Mindestzahl mit den noch fehlenden Aufgaben; eigene Zeile farbig
  hervorgehoben.
* Add: Beim Löschen eines Mitglieds (Backend oder „Konto schließen" mit Löschen) werden seine
  Wertung und Versuche mitgelöscht.
* Add: FEN und Züge werden beim Speichern im Backend nach den Regeln des Imports geprüft.
* Add: Tests für die Übersetzungen (alle Motive in beiden Sprachen, alle Eröffnungsfamilien,
  gleiche Schlüssel in Deutsch und Englisch) und für `eroeffnung.js` (Node.js).
* Change: Rangliste mit eigenen Feldern „Anzahl der Plätze" und „Mindestzahl gespielter
  Aufgaben" in der Legende „Rangliste" statt `numberOfItems` in der Kern-Legende, die andere
  Erweiterungen umbeschriften (etwa zu „Forum-Einstellungen").
* Fix: Knöpfe im Training mit festen Farben für alle Zustände; manche Themes setzten beim
  Überfahren nur einen dunklen Hintergrund, die Schrift blieb schwarz.
* Fix: Meldungen und Fortschrittsanzeige der Importseite mit demselben Seitenabstand wie die
  Formularfelder.
* Fix: Motive, die erst 2026 in die Sammlung kamen (etwa `blindSwineMate`), sind übersetzt.

## Version 1.0.0 (2026-09-27)

* Add: Grundgerüst des Bundles für Contao 4.13 und 5.7: `composer.json`, Contao-Manager-Plugin,
  Bundle-Klasse, DI-Extension und `services.yaml` mit automatischer Dienstregistrierung.
* Add: README mit dem geplanten Funktionsumfang, LICENSE (LGPL-3.0-or-later).
* Add: Tabelle `tl_schachaufgaben` im Aufbau der Lichess-Aufgabensammlung (FEN vor dem
  auslösenden Gegnerzug, UCI-Züge, Glicko-2-Wertung, Beliebtheit, Spiele, Motive, Eröffnung,
  Herkunftspartie). `lichessId` ist eindeutig und unterscheidet Groß- und Kleinschreibung
  (binäre Sortierfolge); eigene Aufgaben tragen dort NULL.
* Add: Backend-Modul „Schachaufgaben" im Bereich Inhalte mit Filter nach Quelle und
  Veröffentlichung, Sortierung nach Wertung, Beliebtheit und Spielen.
* Add: `SchachaufgabeModel` mit `findPublishedByLichessId()` und `getZugliste()`.
* Add: Konsolenbefehl `schachaufgaben:import` für die Lichess-Aufgabensammlung: liest die CSV
  zeilenweise (auch von der Standardeingabe, etwa aus `zstd -dc`), prüft FEN und UCI-Züge,
  filtert nach Beliebtheit, Spielen, Wertung und Motiven und schreibt in Blöcken zu 500 Zeilen.
  Vorhandene Aufgaben werden übersprungen oder mit `--aktualisieren` überschrieben; die eigene
  Wertung bleibt dabei erhalten, außer mit `--wertung-uebernehmen`.
* Add: Unit-Tests für Prüfung, Einlesen und Filter (PHPUnit 9.6) mit einem Auszug der
  Lichess-Datei in `tests/Fixtures/`.
* Add: Frontend-Modul „Schachaufgaben-Training" mit Brett (cm-chessboard 8.14.2) und
  Zugprüfung (chess.js 1.4.0): Gegnerzug, Zugeingabe mit Umwandlungsdialog, jeder Mattzug
  zählt, Wertung nur für den ersten Anlauf, „Lösung zeigen", Angaben zur Aufgabe erst nach
  dem Ende (Motive auf Deutsch). Auch auf dem Handy bedienbar.
* Add: Wertung nach Glicko-2 für Spieler und Aufgaben (`Wertung\Glicko2`), geprüft am
  Rechenbeispiel von Glickman.
* Add: Aufgabenwahl zufällig um die eigene Wertung (±100, Streuung wächst bei Bedarf), damit
  die Aufgaben mit steigender Wertung schwerer werden. Keine Aufgabe wird zweimal gestellt:
  Mitglieder über `tl_schachaufgaben_versuch` (Eintrag schon beim Stellen), Gäste über die
  Sitzung.
* Add: Tabellen `tl_schachaufgaben_spieler` (Wertung je Mitglied) und
  `tl_schachaufgaben_versuch` (gestellte Aufgaben und Ergebnisse).
* Add: JSON-Schnittstelle `/_schachaufgaben/aufgabe` und `/_schachaufgaben/ergebnis`; das
  Ergebnis wird nur als JSON und nur einmal je gestellter Aufgabe angenommen.
* Add: Frontend-Modul „Schachaufgaben-Rangliste" mit Mindestzahl gespielter Aufgaben;
  Namen als „Vorname N.", gesperrte Mitglieder ausgenommen.
* Add: Gegen Contao 4.13.58 und 5.7.7 mit PHP 8.4 im Browser durchgespielt (Gast und
  Mitglied).
* Add: Lichess-Import im Backend (Inhalte → Schachaufgaben → Lichess-Import): CSV aus
  `files/` wählen, Filter wie auf der Konsole, Verarbeitung in Häppchen von acht Sekunden mit
  Fortschrittsanzeige, Anhalten und Fortsetzen an der gemerkten Dateiposition, auch nach
  Schließen des Fensters. Geprüft gegen die vollständige Sammlung (6,1 Mio. Zeilen) mit
  Unterbrechung; das Ergebnis stimmt mit dem Probelauf der Konsole überein.
* Change: `LichessCsvLeser` stützt sich auf die neue Klasse `LichessCsvDatei`, die an einer
  Byte-Position fortsetzen kann. Gepackte Dateien (`.zst`, `.gz`) werden mit Hinweis abgewiesen.
