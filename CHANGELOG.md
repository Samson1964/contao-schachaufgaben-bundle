# Schachaufgaben-Bundle Changelog

## Version 0.1.0 (2026-09-26)

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
