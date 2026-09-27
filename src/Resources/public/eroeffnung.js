/*
 * Übersetzt die Eröffnungsnamen der Lichess-Sammlung.
 *
 * Lichess liefert in der Spalte OpeningTags einen oder mehrere Namen wie
 * „Kings_Pawn_Game Kings_Pawn_Game_Leonardis_Variation". Gezeigt wird nur der
 * letzte, genaueste. Er wird in Familie („Kings_Pawn_Game") und Variante
 * („Leonardis_Variation") zerlegt; beides wird über das Wörterbuch aus der
 * Sprachdatei schachaufgaben_eroeffnungen.php übersetzt.
 *
 * Ohne Wörterbuch (etwa auf Englisch) kommt der Name mit Leerzeichen statt
 * Unterstrichen zurück.
 *
 * @license LGPL-3.0-or-later
 */

/**
 * Übersetzt die Eröffnungsangabe einer Aufgabe.
 *
 * @param {string} tags Inhalt der Spalte OpeningTags, durch Leerzeichen getrennt
 * @param {Object} [wb] Wörterbuch mit familien, phrasen, endungen, zusaetze, figuren, mit
 * @returns {string} Der Name, etwa „Königsbauernspiel: Leonardis-Variante";
 *                   leer, wenn keine Eröffnung angegeben ist
 */
export function eroeffnungUebersetzen(tags, wb) {
    const liste = String(tags || "").trim().split(/\s+/).filter(Boolean)
    if (liste.length === 0) {
        return ""
    }
    const tag = liste[liste.length - 1]
    if (!wb || !wb.familien) {
        return tag.replace(/_/g, " ")
    }

    // Die längste passende Familie gewinnt, damit „Kings_Gambit_Accepted"
    // nicht als „Kings_Gambit" plus Rest „Accepted" zerlegt wird
    let familie = ""
    for (const kandidat of Object.keys(wb.familien)) {
        if ((tag === kandidat || tag.startsWith(kandidat + "_")) && kandidat.length > familie.length) {
            familie = kandidat
        }
    }

    const rest = familie ? tag.slice(familie.length + 1) : tag
    const variante = rest ? varianteUebersetzen(rest, wb) : ""

    if (!familie) {
        return variante
    }
    return variante ? wb.familien[familie] + ": " + variante : wb.familien[familie]
}

/**
 * Übersetzt den Variantenteil eines Namens.
 *
 * Der Teil wird an seinen Schlusswörtern („Variation", „Attack" …) in
 * Abschnitte zerlegt, falls Lichess mehrere Ebenen aneinanderhängt. Zusätze
 * wie „Accepted" werden an den vorigen Abschnitt angehängt, „with" verbindet
 * zum nächsten.
 *
 * @param {string} rest Variantenteil mit Unterstrichen
 * @param {Object} wb   Das Wörterbuch
 * @returns {string} Die übersetzte Variante
 */
function varianteUebersetzen(rest, wb) {
    if (wb.phrasen && wb.phrasen[rest]) {
        return wb.phrasen[rest]
    }

    const teile = []
    let woerter = []
    let verbinden = false

    const hinzufuegen = text => {
        if (verbinden && teile.length > 0) {
            teile[teile.length - 1] += " " + (wb.mit || "with") + " " + text
        } else {
            teile.push(text)
        }
        verbinden = false
    }
    const abschliessen = () => {
        if (woerter.length > 0) {
            hinzufuegen(abschnitt(woerter, wb))
            woerter = []
        }
    }

    for (const wort of rest.split("_")) {
        if (wb.zusaetze && wb.zusaetze[wort]) {
            abschliessen()
            if (teile.length > 0) {
                teile[teile.length - 1] += " " + wb.zusaetze[wort]
            } else {
                teile.push(wb.zusaetze[wort])
            }
        } else if (wort === "with") {
            abschliessen()
            verbinden = true
        } else {
            woerter.push(wort)
            if (wb.endungen && wb.endungen[wort]) {
                abschliessen()
            }
        }
    }
    abschliessen()

    return teile.join(", ")
}

/**
 * Übersetzt einen Abschnitt wie „Najdorf_Variation".
 *
 * @param {string[]} woerter Die Wörter des Abschnitts
 * @param {Object}   wb      Das Wörterbuch
 * @returns {string} Feste Übersetzung, sonst „Eigenname-Endung" wie
 *                   „Najdorf-Variante", sonst die Wörter mit Leerzeichen
 */
function abschnitt(woerter, wb) {
    const schluessel = woerter.join("_")
    if (wb.phrasen && wb.phrasen[schluessel]) {
        return wb.phrasen[schluessel]
    }

    const letztes = woerter[woerter.length - 1]
    const endung = wb.endungen && wb.endungen[letztes]
    const teile = bestimmen(endung ? woerter.slice(0, -1) : woerter, wb)

    if (!endung) {
        // Ohne Endung: Beiwörter mit „e", Vorsilben mit Bindestrich an das nächste Wort
        return teile.reduce((text, teil, i) => {
            const wort = "adj" === teil.art ? teil.text + "e" : "praefix" === teil.art ? teil.text.replace(/-$/, "") + "-" : teil.text
            return text + (i > 0 && !text.endsWith("-") ? " " : "") + wort
        }, "").replace(/-$/, "")
    }

    // Beiwörter am Anfang werden nach dem Geschlecht der Endung gebeugt
    const beugung = {m: "er", n: "es", f: "e", p: "e"}[(wb.geschlecht || {})[letztes]] || "e"
    const vorne = []
    while (teile.length > 0 && ("adj" === teile[0].art || "unv" === teile[0].art)) {
        const teil = teile.shift()
        vorne.push("adj" === teil.art ? teil.text + beugung : teil.text)
    }

    let hinten
    if (teile.length === 0) {
        hinten = endung
    } else if (teile.length === 1 && "praefix" === teile[0].art) {
        const praefix = teile[0].text
        hinten = praefix.endsWith("-") ? praefix + endung : praefix + endung.charAt(0).toLowerCase() + endung.slice(1)
    } else {
        hinten = teile.map(teil => teil.text.replace(/-$/, "")).join("-") + "-" + endung
    }

    return vorne.concat([hinten]).join(" ")
}

/**
 * Ordnet die Wörter eines Abschnitts ein: Beiwort, Ortsname, Vorsilbe oder
 * Eigenname. Mehrteilige Begriffe wie „Kings_Indian" werden zuerst erkannt.
 *
 * @param {string[]} woerter Die Wörter ohne Endung
 * @param {Object}   wb      Das Wörterbuch
 * @returns {{art: string, text: string}[]} Die eingeordneten Teile
 */
function bestimmen(woerter, wb) {
    const teile = []
    for (let i = 0; i < woerter.length; i++) {
        const paar = woerter[i] + "_" + woerter[i + 1]
        if (wb.mehrwort && i + 1 < woerter.length && wb.mehrwort[paar]) {
            const [art, text] = wb.mehrwort[paar].split(":")
            teile.push({art, text})
            i++
        } else if (wb.beiwoerter && wb.beiwoerter[woerter[i]]) {
            teile.push({art: "adj", text: wb.beiwoerter[woerter[i]]})
        } else if (wb.unveraenderlich && wb.unveraenderlich[woerter[i]]) {
            teile.push({art: "unv", text: wb.unveraenderlich[woerter[i]]})
        } else if (wb.praefixe && wb.praefixe[woerter[i]]) {
            teile.push({art: "praefix", text: wb.praefixe[woerter[i]]})
        } else {
            teile.push({art: "name", text: zug(woerter[i], wb)})
        }
    }
    return teile
}

/**
 * Ersetzt in einem Zug den englischen Figurenbuchstaben („Bd3" wird „Ld3").
 *
 * @param {string} wort Ein Wort des Namens
 * @param {Object} wb   Das Wörterbuch
 * @returns {string} Das Wort, bei Zügen mit übersetzter Figur
 */
function zug(wort, wb) {
    const treffer = /^([KQRBN])(x?[a-h][1-8])$/.exec(wort)
    return treffer && wb.figuren && wb.figuren[treffer[1]] ? wb.figuren[treffer[1]] + treffer[2] : wort
}
