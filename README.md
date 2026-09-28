# Schachaufgaben-Bundle für Contao

Schachaufgaben-Training nach dem Vorbild von [lichess.org/training](https://lichess.org/training)
für Contao 4.13 und Contao 5.7 (PHP 8.1 bis 8.4).

> **Stand:** Aufgabentabelle, Import der Lichess-Sammlung im Backend und per Konsole,
> Training im Frontend mit Glicko-2-Wertung und Rangliste.

## Frontend-Module

Unter **Themes → Frontend-Module**, Gruppe „Schachaufgaben":

* **Schachaufgaben-Training** – Brett mit Zugeingabe per Klick oder Ziehen (auch auf dem
  Handy), Anzeige der eigenen Wertung, Knöpfe „Lösung zeigen" und „Nächste Aufgabe".
  Nach Ende einer Aufgabe erscheinen ihre Wertung, die Motive und die Eröffnung auf Deutsch
  und ein Link zur Herkunftspartie, dazu die Knöpfe „Gefällt mir" und „Gefällt mir nicht".
* **Schachaufgaben-Rangliste** – die Mitglieder mit der höchsten Wertung, Name als
  „Vorname N.". Einstellbar: Anzahl der Plätze und Mindestzahl gespielter Aufgaben
  (Standard 20), in einer eigenen Legende „Rangliste". Das angemeldete Mitglied wird
  hervorgehoben; steht es nicht unter den gezeigten Plätzen, erscheint es mit seinem
  tatsächlichen Platz unter der Tabelle, vor Erreichen der Mindestzahl mit dem Hinweis, wie
  viele Aufgaben bis zur Wertung fehlen.

* **Schachaufgaben-Bestenliste** – die ewige Bestenliste: die höchste je erreichte Wertung
  jedes Mitglieds mit dem Datum, an dem sie erreicht wurde. Einstellbar ist die Anzahl der
  Plätze; auch hier erscheint der eigene Platz unter der Tabelle.

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
* **Keine begonnene Aufgabe kommt zweimal.** Gespeichert wird erst mit dem ersten Zug auf
  dem Brett (oder mit „Lösung zeigen"): bei Mitgliedern in `tl_schachaufgaben_versuch`, bei
  Gästen in einer Merkliste der Sitzung. Das bloße Aufrufen einer Aufgabe hinterlässt
  nichts; eine nur angesehene und übersprungene Aufgabe kann später wieder kommen.
* Die Wertung einer Aufgabe ändert sich nur durch Mitglieder, damit Gäste sie nicht
  verfälschen können.
* Gäste behalten ihre Wertung nur für die Sitzung und erscheinen nicht in der Rangliste.

### Bestwertung, erste Nutzung und Monatsranglisten

* Für jedes Mitglied werden die **höchste erreichte Wertung** mit Datum und das Datum der
  **ersten Benutzung** gespeichert (`tl_schachaufgaben_spieler`: `bestWertung`, `bestDatum`,
  `ersteNutzung`).
* Als Bestwertung zählt nur eine **gesicherte** Wertung, also eine mit einer Abweichung von
  höchstens 110 (`Training::GESICHERTE_ABWEICHUNG`), wie bei Lichess die Grenze zu
  „vorläufig". Sonst wäre die Bestwertung oft nur der Ausschlag nach der ersten gelösten
  Aufgabe. Gesichert ist eine Wertung meist nach 12 bis 15 Aufgaben.
* Ein **Cronjob** speichert zum Monatsersten die Rangliste in
  `tl_schachaufgaben_ranglistenstand`: je Mitglied mit mindestens einer Aufgabe Platz, Name
  („Vorname N."), Wertung, Abweichung, Aufgaben, gelöste Aufgaben und Bestwertung. Er läuft
  stündlich und legt den Stand beim ersten Lauf nach Monatsbeginn an; alle weiteren Läufe
  finden ihn vor. Voraussetzung ist, dass der Contao-Cron läuft (Seitenaufrufe oder
  `contao:cron`). Von Hand: `php vendor/bin/contao-console schachaufgaben:rangliste-speichern`.
* Im Backend unter **Inhalte → Schachaufgaben → Monatsranglisten** lassen sich die Stände
  ansehen.
* Beim Update von 1.1 trägt eine Migration die erste Nutzung (ältester Versuch) und, bei
  gesicherter Wertung, die aktuelle Wertung als Bestwertung nach. Frühere Höchststände waren
  nicht gespeichert und lassen sich nicht zurückgewinnen.

### Eigene Statistik und Bewertung

Beliebtheit und Spielzahl von Lichess dienen nur als **Filter beim Import** und werden nicht
gespeichert. Die Website führt ihre eigene Statistik:

* `spiele` zählt die Lösungsversuche auf dieser Website, von Mitgliedern und Gästen.
* Nach dem Ende einer Aufgabe kann man sie mit „Gefällt mir" oder „Gefällt mir nicht"
  bewerten, die Stimme ändern oder durch einen zweiten Klick zurücknehmen. Je Mitglied und
  Aufgabe zählt eine Stimme (bei Gästen je Sitzung).
* `beliebtheit` = 100 × (Gefällt mir − Gefällt mir nicht) ÷ Stimmen, also von -100 (alle
  dagegen) bis 100 (alle dafür). Die Spanne ist dieselbe wie bei Lichess: Sie ist ein Anteil,
  keine Summe, und macht Aufgaben mit wenigen und vielen Stimmen vergleichbar.

Beim Update von 1.0.0 setzt eine Migration die dort übernommenen Lichess-Werte einmalig auf 0.

### Eröffnungen

Die Eröffnungsnamen von Lichess (etwa `Sicilian_Defense_Najdorf_Variation`) werden im
Training übersetzt: „Sizilianische Verteidigung: Najdorf-Variante". Das Wörterbuch in
`languages/de/schachaufgaben_eroeffnungen.php` kennt alle 156 Familien der Sammlung, häufige
Variantennamen und die Beugung von Beiwörtern; Eigennamen bleiben stehen, Züge bekommen
deutsche Figurenbuchstaben („Bd3" wird „Ld3"). In anderen Sprachen erscheint der englische Name.

### Technik

Das Brett ist [cm-chessboard](https://github.com/shaack/cm-chessboard) 8.14.2 (MIT), die
Zugprüfung [chess.js](https://github.com/jhlywa/chess.js) 1.4.0 (BSD-2-Clause). Beide liegen
unverändert (nur ohne Source-Map-Verweise) unter `src/Resources/public/vendor/` samt
Lizenzdatei. Das Frontend spricht über vier Routen mit dem Server:

| Route | Zweck |
| --- | --- |
| `GET /_schachaufgaben/aufgabe` | nächste Aufgabe stellen |
| `POST /_schachaufgaben/beginn` | erster Zug, JSON `{"id": 123}`; erst jetzt wird gespeichert |
| `POST /_schachaufgaben/ergebnis` | Ergebnis melden, JSON `{"id": 123, "geloest": true}` |
| `POST /_schachaufgaben/bewertung` | Aufgabe bewerten, JSON `{"id": 123, "stimme": 1}` (1, -1 oder 0) |

Ergebnis und Bewertung werden nur als `application/json` angenommen (Schutz vor fremden
Formularen); das Ergebnis nur für eine gestellte, noch nicht gewertete Aufgabe, die Bewertung
erst nach dem Ergebnis.

Beim Löschen eines Mitglieds (Backend oder Frontend-Modul „Konto schließen" mit Löschen)
werden seine Wertung, Versuche und Einträge in den Monatsranglisten mitgelöscht. Im Backend werden FEN und Züge beim Speichern
nach denselben Regeln wie beim Import geprüft.

## Aufbau einer Aufgabe

| Feld | Inhalt |
| --- | --- |
| `fen` | Stellung **vor** dem ersten Zug |
| `zuege` | UCI-Züge, durch Leerzeichen getrennt. Der erste ist der Gegnerzug, der die Aufgabe auslöst; danach wechseln Lösung und Antwort. |
| `wertung`, `wertungAbweichung`, `wertungVolatilitaet` | Schwierigkeit nach Glicko-2 |
| `spiele`, `beliebtheit`, `gefaellt`, `gefaelltNicht` | eigene Statistik der Website (siehe oben) |
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
| `--min-beliebtheit=N` | nur Aufgaben mit mindestens dieser Beliebtheit bei Lichess (-100 bis 100) |
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
weil `tests/bootstrap.php` dann einen eigenen Autoloader registriert. Sie prüft unter anderem,
dass alle Motive und Eröffnungsfamilien der Lichess-Sammlung übersetzt sind.

Die Übersetzung der Eröffnungen hat eigene Tests für Node.js; sie liest das Wörterbuch über PHP
aus der Sprachdatei:

```bash
node --test tests/js/eroeffnung.test.mjs
```

## Geplant

* Import eigener Aufgaben als PGN mit `[FEN]`-Kopf.

## Installation

```bash
composer require schachbulle/contao-schachaufgaben-bundle
```

Danach die Datenbank über den Contao Manager oder `contao:migrate` aktualisieren.

## Lizenz

LGPL-3.0-or-later, siehe [LICENSE](LICENSE).
