# Schachaufgaben-Bundle für Contao

Schachaufgaben-Training nach dem Vorbild von [lichess.org/training](https://lichess.org/training)
für Contao 4.13 und Contao 5.7 (PHP 7.4 bis 8.4).

> **Stand:** Grundgerüst mit Aufgabentabelle und Backend-Modul. Import, Frontend,
> Wertung und Ranglisten sind geplant und noch nicht umgesetzt.

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
