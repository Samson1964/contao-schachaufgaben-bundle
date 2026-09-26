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
