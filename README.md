# Schachaufgaben-Bundle für Contao

Schachaufgaben-Training nach dem Vorbild von [lichess.org/training](https://lichess.org/training)
für Contao 4.13 und Contao 5.7 (PHP 7.4 bis 8.4).

> **Stand:** Aufgabentabelle, Backend-Modul und Import der Lichess-Sammlung.
> Frontend, Wertung und Ranglisten sind geplant und noch nicht umgesetzt.

## Aufbau einer Aufgabe

| Feld | Inhalt |
| --- | --- |
| `fen` | Stellung **vor** dem ersten Zug |
| `zuege` | UCI-Züge, durch Leerzeichen getrennt. Der erste ist der Gegnerzug, der die Aufgabe auslöst; danach wechseln Lösung und Antwort. |
| `wertung`, `wertungAbweichung`, `wertungVolatilitaet` | Schwierigkeit nach Glicko-2 |
| `beliebtheit`, `spiele` | Lichess-Beliebtheit (-100 bis 100) und Zahl der Versuche |
| `motive`, `eroeffnung` | Motive und Eröffnung im Lichess-Format |
| `quelle`, `lichessId`, `partieUrl` | Herkunft; `lichessId` ist eindeutig und unterscheidet Groß- und Kleinschreibung |

Die Aufgaben werden im Backend unter **Inhalte → Schachaufgaben** verwaltet.

## Aufgaben von Lichess importieren

Lichess stellt seine komplette Aufgabensammlung unter
[database.lichess.org](https://database.lichess.org/#puzzles) gemeinfrei (CC0) zur Verfügung:
mehrere Millionen Aufgaben aus echten Partien, von Stockfish geprüft. Die Datei
`lichess_db_puzzle.csv.zst` ist mit Zstandard gepackt; sie kann vorher entpackt oder direkt
durchgereicht werden:

```bash
zstd -dc lichess_db_puzzle.csv.zst | php vendor/bin/contao-console schachaufgaben:import - --min-beliebtheit=80 --min-spiele=1000 --limit=100000
```

| Option | Wirkung |
| --- | --- |
| `--min-beliebtheit=N` | nur Aufgaben mit mindestens dieser Beliebtheit (-100 bis 100) |
| `--min-spiele=N` | nur Aufgaben, die bei Lichess mindestens N-mal gespielt wurden |
| `--min-wertung=N`, `--max-wertung=N` | Schwierigkeitsbereich |
| `--motiv=NAME` | nur Aufgaben mit diesem Motiv, mehrfach angebbar (eines genügt), z. B. `--motiv=fork --motiv=mateIn2` |
| `--limit=N` | höchstens N Aufgaben übernehmen |
| `--aktualisieren` | vorhandene Aufgaben mit den Lichess-Daten überschreiben (ohne Wertung) |
| `--wertung-uebernehmen` | beim Aktualisieren auch Wertung und Abweichung von Lichess übernehmen |
| `--unveroeffentlicht` | neue Aufgaben nicht sofort veröffentlichen |
| `--probelauf` | nur zählen, nichts schreiben |
| `-v` | die ersten zwanzig fehlerhaften Zeilen mit Zeilennummer anzeigen |

Hinweise:

* Die Lichess-Datei ist nach Kennung sortiert, die Kennungen sind zufällig vergeben. `--limit`
  liefert deshalb eine zufällige Auswahl über alle Schwierigkeiten.
* Vorhandene Aufgaben werden ohne `--aktualisieren` übersprungen. Ein abgebrochener Import
  kann daher einfach neu gestartet werden; `--limit` zählt dabei auch die übersprungenen mit.
* Jede Zeile wird auf eine formal gültige FEN und UCI-Zugfolge geprüft; kaputte Zeilen werden
  gezählt und übersprungen, nicht importiert.
* Mit `--limit` hört der Import auf zu lesen, bevor `zstd` fertig ist. Die Meldung
  `zstd: error 70 : Write error : … Broken pipe` ist dann harmlos; mit `zstd -dcq … 2>/dev/null`
  bleibt sie aus.

Messwerte (Sammlung vom 9. September 2026, PHP 8.4, MariaDB, lokaler Rechner):

| Lauf | Ergebnis | Dauer |
| --- | --- | --- |
| Probelauf über die ganze Datei | 6.100.952 Aufgaben, keine fehlerhaft | 83 s |
| `--min-beliebtheit=80 --min-spiele=1000` | 1.848.994 Aufgaben erfüllen die Filter | 83 s |
| dasselbe mit `--limit=100000` | 100.000 Aufgaben geschrieben (Wertung 399 bis 3208, Mittel 1579) | 10–11 s |
| Wiederholung | nichts geschrieben, alle übersprungen | 9 s |

## Tests

```bash
vendor/bin/phpunit
```

Ohne installierte Abhängigkeiten läuft die Testsuite auch mit einem eigenständigen PHPUnit 9.6,
weil `tests/bootstrap.php` dann einen eigenen Autoloader registriert.

## Geplanter Umfang

* Aufgaben in der Tabelle `tl_schachaufgaben`: Ausgangsstellung (FEN), Lösungszüge,
  Schwierigkeit als Wertungszahl und Motive (Gabel, Fesselung, Matt in 2 …).
* Import der gemeinfreien Aufgabensammlung von Lichess (CC0,
  [database.lichess.org](https://database.lichess.org/#puzzles)) per Konsolenbefehl,
  mit Filtern nach Beliebtheit, Anzahl der Spiele und Schwierigkeit.
* Zusätzlicher Import eigener Aufgaben als PGN mit `[FEN]`-Kopf.
* Spiel als angemeldetes Mitglied (mit dauerhafter Wertung) oder als Gast (Wertung nur
  für die Sitzung).
* Wertung nach Glicko-2 für Spieler und Aufgaben.
* Ranglisten der Mitglieder.

## Installation

```bash
composer require schachbulle/contao-schachaufgaben-bundle
```

Danach die Datenbank über den Contao Manager oder `contao:migrate` aktualisieren.

## Lizenz

LGPL-3.0-or-later, siehe [LICENSE](LICENSE).
