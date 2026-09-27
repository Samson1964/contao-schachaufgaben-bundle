/*
 * Tests für eroeffnung.js. Aufruf: node --test tests/js
 *
 * Das Wörterbuch wird aus der Sprachdatei gelesen (über PHP), damit der Test
 * genau das prüft, was im Frontend ankommt.
 */

import {test} from "node:test"
import assert from "node:assert/strict"
import {execFileSync} from "node:child_process"
import {readFileSync} from "node:fs"
import {fileURLToPath} from "node:url"
import {dirname, join} from "node:path"
import {eroeffnungUebersetzen} from "../../src/Resources/public/eroeffnung.js"

const wurzel = join(dirname(fileURLToPath(import.meta.url)), "..", "..")
const php = process.env.PHP_BINARY || "php"
const wb = JSON.parse(execFileSync(php, ["-r", `
    include '${join(wurzel, "src/Resources/contao/languages/de/schachaufgaben_eroeffnungen.php").replace(/\\/g, "/")}';
    echo json_encode($GLOBALS['TL_LANG']['MSC']['schachaufgaben_eroeffnungen']);
`]).toString())

test("Familie und Eigenname", () => {
    assert.equal(eroeffnungUebersetzen("Kings_Pawn_Game Kings_Pawn_Game_Leonardis_Variation", wb), "Königsbauernspiel: Leonardis-Variante")
    assert.equal(eroeffnungUebersetzen("Sicilian_Defense Sicilian_Defense_Najdorf_Variation", wb), "Sizilianische Verteidigung: Najdorf-Variante")
})

test("Nur Familie, feste Phrasen, Zusätze und Züge", () => {
    assert.equal(eroeffnungUebersetzen("Ruy_Lopez", wb), "Spanische Partie")
    assert.equal(eroeffnungUebersetzen("Kings_Gambit_Accepted", wb), "Angenommenes Königsgambit")
    assert.equal(eroeffnungUebersetzen("French_Defense French_Defense_Advance_Variation", wb), "Französische Verteidigung: Vorstoßvariante")
    assert.equal(eroeffnungUebersetzen("Queens_Pawn_Game Queens_Pawn_Game_Other_variations", wb), "Damenbauernspiel: Sonstige Varianten")
    assert.equal(eroeffnungUebersetzen("London_System_with_Bd3", wb), "Londoner System mit Ld3")
})

test("Ohne Wörterbuch nur Leerzeichen statt Unterstrichen", () => {
    assert.equal(eroeffnungUebersetzen("Ruy_Lopez Ruy_Lopez_Berlin_Defense", {}), "Ruy Lopez Berlin Defense")
    assert.equal(eroeffnungUebersetzen("", wb), "")
})

test("Alle Namen der Sammlung bekommen eine deutsche Familie und keine englischen Schlusswörter", () => {
    const namen = readFileSync(join(wurzel, "tests/Fixtures/lichess_eroeffnungen.txt"), "utf8").split(/\r?\n/).filter(Boolean)
    const englisch = /\b(Variation|variations|Defense|Attack|Opening|Game|Countergambit|Counterattack|Accepted|Declined|with)\b/
    const fehler = namen
        .map(name => [name, eroeffnungUebersetzen(name, wb)])
        .filter(([name, deutsch]) => englisch.test(deutsch) || !Object.keys(wb.familien).some(f => name === f || name.startsWith(f + "_")))
    assert.deepEqual(fehler, [])
    assert.equal(namen.length, 1589)
})
