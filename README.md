# Schachaufgaben-Bundle für Contao

Schachaufgaben-Training nach dem Vorbild von [lichess.org/training](https://lichess.org/training)
für Contao 4.13 und Contao 5.7 (PHP 7.4 bis 8.4).

> **Stand:** Aufgabentabelle, Import der Lichess-Sammlung im Backend und per Konsole,
> Training im Frontend mit Glicko-2-Wertung und Rangliste.

## Frontend-Module

Unter **Themes → Frontend-Module**, Gruppe „Schachaufgaben":

* **Schachaufgaben-Training** – Brett mit Zugeingabe per Klick oder Ziehen (auch auf dem
  Handy), Anzeige der eigenen Wertung, Knöpfe „Lösung zeigen" und „Nächste Aufgabe".
  Nach Ende einer Aufgabe erscheinen ihre Wertung, die Motive auf Deutsch, die Eröffnung
  und ein Link zur Herkunftspartie.
* **Schachaufgaben-Rangliste** – die Mitglieder mit der höchsten Wertung, Name als
  „Vorname N.". Einstellbar: Anzahl der Plätze und Mindestzahl gespielter Aufgaben
  (Standard 20). Das angemeldete Mitglied wird hervorgehoben.

Für angemeldete Mitglieder gehört ein Anmeldemodul von Contao auf die Seite; ohne
Anmeldung wird als Gast gespielt.

### So läuft eine Aufgabe

1. Die Stellung erscheint, der Gegner macht den ersten Zug.
2. Der Spieler sucht den besten Zug. Richtige Züge beantwortet der Gegner mit dem nächsten
   Zug der Lösung, bis die Zugfolge durchgespielt ist. Jeder andere Mattzug zählt ebenfalls
   als richtig.
3. Gewertet wird nur der **erste Anlauf**: Ein falscher Zug oder „Lösung zeigen" zählt als
   nicht gelöst. Danach darf weiterprobiert werden, ohne dass sich die Wertung ändert.

### Wertung und Auswahl der Aufgaben

* Spieler und Aufgabe treten wie bei Lichess nach **Glicko-2** gegeneinander an: Löst der
  Spieler die Aufgabe, gewinnt er, sonst gewinnt die Aufgabe. Neue Spieler starten mit
  1500 ± 350, die Wertung bewegt sich anfangs deshalb stark und wird mit jeder Aufgabe
  sicherer.
* Die nächste Aufgabe liegt **zufällig in der Nähe der eigenen Wertung** (±100 Punkte, aus
  den zehn nächstgelegenen wird gelost). Mit steigender Wertung werden die Aufgaben also von
  selbst schwerer, nach Fehlern leichter.
* **Keine Aufgabe kommt zweimal.** Bei Mitgliedern wird jede gestellte Aufgabe sofort in
  `tl_schachaufgaben_versuch` vermerkt – auch übersprungene oder durch Neuladen verworfene.
  Gäste haben eine Merkliste in der Sitzung, die für die Dauer des Besuchs gilt.
* Die Wertung einer Aufgabe ändert sich nur durch Mitglieder, damit Gäste sie nicht
  verfälschen können.
* Gäste behalten ihre Wertung nur für die Sitzung und erscheinen nicht in der Rangliste.

### Technik

Das Brett ist [cm-chessboard](https://github.com/shaack/cm-chessboard) 8.14.2 (MIT), die
Zugprüfung [chess.js](https://github.com/jhlywa/chess.js) 1.4.0 (BSD-2-Clause). Beide liegen
unverändert (nur ohne Source-Map-Verweise) unter `src/Resources/public/vendor/` samt
Lizenzdatei. Das Frontend spricht über zwei Routen mit dem Server:

| Route | Zweck |
| --- | --- |
| `GET /_schachaufgaben/aufgabe` | nächste Aufgabe stellen |
| `POST /_schachaufgaben/ergebnis` | Ergebnis melden, JSON `{"id": 123, "geloest": true}` |

Das Ergebnis wird nur als `application/json` angenommen (Schutz vor fremden Formularen) und
nur für eine gestellte, noch nicht gewertete Aufgabe.

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
`lichess_db_puzzle.csv.zst` ist mit Zstandard gepackt.

### Im Backend

1. Die Datei auf dem eigenen Rechner entpacken (`zstd -d lichess_db_puzzle.csv.zst` oder
   7-Zip ZS). Die CSV ist gut 1 GB groß.
2. Die CSV per FTP irgendwo unter `files/` ablegen (die Upload-Grenze des Formulars reicht
   dafür nicht).
3. Im Backend unter **Inhalte → Schachaufgaben → Lichess-Import** die Datei wählen, Filter
   setzen und „Import starten".

Der Import läuft in Häppchen von etwa acht Sekunden, die das Skript der Seite nacheinander
anstößt; so bleibt jeder Aufruf weit unter der `max_execution_time` des Servers. Eine
Fortschrittsanzeige zeigt gelesene, ausgefilterte und übernommene Zeilen. Der Import lässt
sich anhalten; ist das Fenster geschlossen oder die Verbindung weg, bietet die Seite beim
nächsten Aufruf „Fortsetzen" an derselben Stelle an. Die Filter entsprechen denen des
Konsolenbefehls.

Gemessen (PHP-Entwicklungsserver, lokale MariaDB): die ganze Datei mit 6,1 Mio. Zeilen in
1:49 Minuten, 50.000 neue Aufgaben in 12 Sekunden.

### Per Konsole

Mit SSH-Zugang kann die gepackte Datei direkt durchgereicht werden:

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
