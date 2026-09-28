/*
 * Tests für pgn.js. Aufruf: node --test tests/js/pgn.test.mjs
 */

import {test} from "node:test"
import assert from "node:assert/strict"
import {pgnZerlegen, aufgabeAusPgn} from "../../src/Resources/public/pgn.js"
import {Chess} from "../../src/Resources/public/vendor/chess.js/chess.js"

// Zwei Aufgaben wie aus ChessBase: Kommentare, Varianten, Bewertungszeichen,
// Zugnummern mit „…" und am Ende eine Antwort des Gegners
const DATEI = `[Event "Aufgabe 1"]
[White "Weiß"]
[Black "Schwarz"]
[SetUp "1"]
[FEN "6k1/5ppp/8/8/8/8/5PPP/3R2K1 w - - 0 1"]
[Themes "backRankMate mateIn1"]
[Rating "900"]

1. Rd8# {Grundreihenmatt} 1-0

[Event "Aufgabe 2"]
[SetUp "1"]
[FEN "r1bqkb1r/pppp1ppp/2n2n2/4p2Q/2B1P3/8/PPPP1PPP/RNB1K1NR w KQkq - 4 4"]

4. Qxf7# $1 (4. Qxe5+? Nxe5) 1-0

[Event "Aufgabe 3"]
[FEN "4k3/8/8/8/8/8/4P3/4K3 w - - 0 1"]

1. e4 Kd7 2. e5 Ke6 *
`

test("Zerlegen in Partien", () => {
    const partien = pgnZerlegen("﻿" + DATEI)
    assert.equal(partien.length, 3)
    assert.match(partien[1], /Aufgabe 2/)
})

test("Spieler zieht zuerst, Kopfangaben werden übernommen", () => {
    const aufgabe = aufgabeAusPgn(pgnZerlegen(DATEI)[0], Chess, true)
    assert.deepEqual(aufgabe.zuege, ["d1d8"])
    assert.equal(aufgabe.fen, "6k1/5ppp/8/8/8/8/5PPP/3R2K1 w - - 0 1")
    assert.equal(aufgabe.motive, "backRankMate mateIn1")
    assert.equal(aufgabe.wertung, 900)
    assert.equal(aufgabe.titel, "Aufgabe 1, Weiß – Schwarz")
})

test("Varianten, Kommentare und Bewertungszeichen zählen nicht", () => {
    const aufgabe = aufgabeAusPgn(pgnZerlegen(DATEI)[1], Chess, true)
    assert.deepEqual(aufgabe.zuege, ["h5f7"])
    assert.equal(aufgabe.wertung, null)
})

test("Eine abschließende Antwort des Gegners wird weggelassen", () => {
    // e4 Kd7 e5 Ke6: vier Züge, der Löser (Weiß) zog zuletzt mit e5
    assert.deepEqual(aufgabeAusPgn(pgnZerlegen(DATEI)[2], Chess, true).zuege, ["e2e4", "e8d7", "e4e5"])
    // Im Lichess-Stil ist der erste Zug der des Gegners: gerade Zahl, nichts abzuschneiden
    assert.deepEqual(aufgabeAusPgn(pgnZerlegen(DATEI)[2], Chess, false).zuege, ["e2e4", "e8d7", "e4e5", "d7e6"])
})

test("Unbrauchbare Partien ergeben verständliche Fehler", () => {
    assert.throws(() => aufgabeAusPgn("[Event \"x\"]\n\n1. e4 e5 *", Chess, true), /Ausgangsstellung/)
    assert.throws(() => aufgabeAusPgn("[FEN \"4k3/8/8/8/8/8/4P3/4K3 w - - 0 1\"]\n\n*", Chess, true), /keinen Lösungszug/)
    assert.throws(() => aufgabeAusPgn("[FEN \"4k3/8/8/8/8/8/4P3/4K3 w - - 0 1\"]\n\n1. Qh5 *", Chess, true), /nicht lesen/)
})
