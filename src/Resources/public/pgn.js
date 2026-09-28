/*
 * Macht aus einer PGN-Datei Schachaufgaben im Format des Trainings.
 *
 * Jede Partie der Datei wird zu einer Aufgabe: Ausgangsstellung aus dem Tag
 * [FEN], Lösung aus der Hauptvariante (Varianten und Kommentare zählen nicht),
 * umgerechnet in UCI-Züge („f3e5"), wie sie auch die Lichess-Aufgaben haben.
 * Die Umrechnung braucht einen vollständigen Zuggenerator; dafür wird chess.js
 * übergeben, das ohnehin im Bundle liegt.
 *
 * Wer zuerst zieht, legt der Aufrufer fest:
 * - spielerZuerst = true: Die Stellung ist die Aufgabe, der Löser ist am Zug
 *   (üblich bei Aufgabensammlungen, etwa aus ChessBase).
 * - spielerZuerst = false: Der erste Zug ist der des Gegners, wie bei Lichess.
 * Eine Aufgabe endet immer mit einem Zug des Lösers; eine abschließende
 * Antwort des Gegners in der PGN wird deshalb weggelassen.
 *
 * @license LGPL-3.0-or-later
 */

/**
 * Zerlegt den Text einer PGN-Datei in einzelne Partien.
 *
 * Eine neue Partie beginnt mit einer Kopfzeile („[Event …]"), die nicht direkt
 * auf eine andere Kopfzeile folgt.
 *
 * @param {string} text Inhalt der PGN-Datei
 * @returns {string[]} Die Partien als eigene PGN-Texte, leere weggelassen
 */
export function pgnZerlegen(text) {
    const partien = []
    let aktuell = []
    let vorherKopf = false

    for (const zeile of String(text).replace(/^﻿/, "").split(/\r?\n/)) {
        const kopf = /^\s*\[/.test(zeile)
        if (kopf && !vorherKopf && aktuell.some(z => z.trim() !== "")) {
            partien.push(aktuell.join("\n"))
            aktuell = []
        }
        aktuell.push(zeile)
        if (zeile.trim() !== "") {
            vorherKopf = kopf
        }
    }
    if (aktuell.some(z => z.trim() !== "")) {
        partien.push(aktuell.join("\n"))
    }
    return partien
}

/**
 * Macht aus einer Partie eine Aufgabe.
 *
 * @param {string}   pgn           Eine Partie als PGN-Text
 * @param {Function} Chess         Die Klasse Chess aus chess.js
 * @param {boolean}  spielerZuerst true, wenn der Löser in der FEN am Zug ist
 * @returns {{fen: string, zuege: string[], spielerZuerst: boolean, motive: string, wertung: number|null, titel: string}}
 * @throws {Error} Mit deutscher Meldung, wenn die Partie keine Aufgabe ergibt
 */
export function aufgabeAusPgn(pgn, Chess, spielerZuerst) {
    const chess = new Chess()
    try {
        chess.loadPgn(pgn)
    } catch (fehler) {
        throw new Error("Die PGN lässt sich nicht lesen: " + fehler.message)
    }

    const kopf = chess.getHeaders()
    const titel = [kopf.Event, kopf.White && kopf.Black ? kopf.White + " – " + kopf.Black : ""]
        .filter(teil => teil && teil !== "?").join(", ")
    const fenKopf = Object.keys(kopf).find(schluessel => schluessel.toLowerCase() === "fen")

    if (!fenKopf) {
        throw new Error("Es fehlt die Ausgangsstellung (Tag [FEN]).")
    }

    // Die FEN über chess.js normalisieren, damit gleiche Stellungen gleich aussehen
    const fen = new Chess(kopf[fenKopf]).fen()
    const zuege = chess.history({verbose: true}).map(zug => zug.from + zug.to + (zug.promotion || ""))

    // Die Aufgabe endet mit einem Zug des Lösers: bei spielerZuerst nach einer
    // ungeraden Zahl von Zügen, sonst nach einer geraden
    if ((zuege.length % 2 === 0) === spielerZuerst && zuege.length > 0) {
        zuege.pop()
    }

    if (zuege.length < (spielerZuerst ? 1 : 2)) {
        throw new Error(spielerZuerst
            ? "Die Partie enthält keinen Lösungszug."
            : "Die Partie braucht mindestens den Gegnerzug und einen Lösungszug.")
    }

    const wertung = parseInt(kopf.Rating || kopf.PuzzleRating || kopf.Wertung || "", 10)

    return {
        fen,
        zuege,
        spielerZuerst,
        motive: String(kopf.Themes || kopf.Motive || "").trim(),
        wertung: Number.isFinite(wertung) ? wertung : null,
        titel
    }
}
