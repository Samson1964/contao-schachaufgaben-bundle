/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training.
 *
 * Ablauf einer Aufgabe:
 *   1. Aufgabe vom Server holen (FEN vor dem Gegnerzug und alle Züge in UCI).
 *   2. Den ersten Zug – den des Gegners – automatisch ausführen.
 *   3. Der Spieler zieht. Stimmt der Zug mit der Lösung überein (oder setzt
 *      er matt), antwortet der Gegner mit dem nächsten Zug der Lösung.
 *   4. Ist die Zugfolge durchgespielt, ist die Aufgabe gelöst.
 *
 * Gewertet wird nur der erste Anlauf: Beim ersten falschen Zug oder beim
 * Aufdecken der Lösung geht „nicht gelöst" an den Server, danach darf der
 * Spieler ohne Einfluss auf die Wertung weiterprobieren.
 *
 * @license LGPL-3.0-or-later
 */

import {Chessboard, COLOR, INPUT_EVENT_TYPE, BORDER_TYPE} from "./vendor/cm-chessboard/src/Chessboard.js"
import {Markers, MARKER_TYPE} from "./vendor/cm-chessboard/src/extensions/markers/Markers.js"
import {PromotionDialog, PROMOTION_DIALOG_RESULT_TYPE} from "./vendor/cm-chessboard/src/extensions/promotion-dialog/PromotionDialog.js"
import {Chess} from "./vendor/chess.js/chess.js"

// Eigene Module mit derselben Versionsangabe laden, mit der training.js
// eingebunden wurde („?v=…"), damit nach einem Update kein Browser eine alte
// Fassung aus seinem Zwischenspeicher nimmt. Ein statischer Import kann die
// Angabe nicht übernehmen, deshalb der dynamische Import.
const {eroeffnungUebersetzen} = await import("./eroeffnung.js" + new URL(import.meta.url).search)

/** Pause zwischen den Zügen, damit der Spieler sie verfolgen kann (ms). */
const PAUSE = 500

/** Markierung des letzten Zuges und falscher Züge. */
const MARKER_ZUG = MARKER_TYPE.square
const MARKER_FALSCH = MARKER_TYPE.frameDanger

const warten = ms => new Promise(fertig => setTimeout(fertig, ms))

class SchachaufgabenTraining {

    /**
     * Baut Brett und Bedienelemente in einem Container des Moduls auf und
     * lädt die erste Aufgabe.
     *
     * @param {HTMLElement} element Der Container mit data-konfiguration
     */
    constructor(element) {
        this.element = element
        this.konfiguration = JSON.parse(element.dataset.konfiguration)
        this.texte = this.konfiguration.texte
        this.feld = {
            wertung: element.querySelector("[data-wertung]"),
            differenz: element.querySelector("[data-differenz]"),
            zaehler: element.querySelector("[data-zaehler]"),
            status: element.querySelector("[data-status]"),
            info: element.querySelector("[data-info]"),
            gast: element.querySelector("[data-gast]"),
            loesung: element.querySelector('[data-aktion="loesung"]'),
            naechste: element.querySelector('[data-aktion="naechste"]'),
            bewertung: element.querySelector("[data-bewertung]"),
            stimmen: element.querySelectorAll("[data-stimme]")
        }

        this.brett = new Chessboard(element.querySelector(".schachaufgaben-brett"), {
            assetsUrl: this.konfiguration.assetsUrl,
            style: {borderType: BORDER_TYPE.frame, pieces: {file: "pieces/standard.svg"}, animationDuration: 250},
            extensions: [{class: Markers, props: {autoMarkers: null}}, {class: PromotionDialog}]
        })

        this.feld.loesung.addEventListener("click", () => this.loesungZeigen())
        this.feld.naechste.addEventListener("click", () => this.laden())
        this.feld.stimmen.forEach(knopf => knopf.addEventListener("click", () => this.bewerten(Number(knopf.dataset.stimme))))
        this.eingabe = this.eingabe.bind(this)

        this.laden()
    }

    /**
     * Holt die nächste Aufgabe und spielt den auslösenden Gegnerzug.
     */
    async laden() {
        this.brett.disableMoveInput()
        this.brett.removeMarkers()
        this.feld.naechste.hidden = true
        this.feld.loesung.disabled = true
        this.feld.info.hidden = true
        this.feld.info.textContent = ""
        this.feld.bewertung.hidden = true
        this.stimmeAnzeigen(0)
        this.feld.differenz.textContent = ""
        this.status(this.texte.laden)

        let daten
        try {
            const antwort = await fetch(this.konfiguration.aufgabeUrl, {
                headers: {"Accept": "application/json", "X-Requested-With": "XMLHttpRequest"},
                credentials: "same-origin",
                cache: "no-store"
            })
            daten = await antwort.json()
            if (antwort.status === 404 && daten.fertig) {
                this.status(this.texte.fertig, "erfolg")
                return
            }
            if (!antwort.ok) {
                throw new Error("HTTP " + antwort.status)
            }
        } catch (fehler) {
            console.error("Schachaufgaben:", fehler)
            this.status(this.texte.fehler, "fehler")
            return
        }

        this.aufgabe = daten
        this.index = 0
        this.fehlerGemacht = false
        this.gemeldet = false
        this.beendet = false
        this.ergebnis = null
        this.chess = new Chess(daten.fen)

        // Der Gegner zieht zuerst; der Spieler hat die andere Farbe
        this.farbe = this.chess.turn() === "w" ? COLOR.black : COLOR.white
        this.spielerAnzeigen(daten.spieler)

        await this.brett.setOrientation(this.farbe)
        await this.brett.setPosition(daten.fen, false)
        await warten(PAUSE)
        await this.gegnerZieht()
    }

    /**
     * Führt den nächsten Zug der Lösung für den Gegner aus und gibt danach
     * die Eingabe für den Spieler frei.
     */
    async gegnerZieht() {
        const zug = this.ziehen(this.aufgabe.zuege[this.index])
        this.index++
        await this.brett.setPosition(this.chess.fen(), true)
        this.zugMarkieren(zug)

        if (this.index >= this.aufgabe.zuege.length) {
            return
        }

        this.status(this.farbe === COLOR.white ? this.texte.amZugWeiss : this.texte.amZugSchwarz)
        this.feld.loesung.disabled = false
        this.brett.enableMoveInput(this.eingabe, this.farbe)
    }

    /**
     * Ereignisbehandlung der Zugeingabe von cm-chessboard.
     *
     * @param {Object} ereignis Das Ereignis des Bretts
     * @returns {boolean|undefined} Ob die Eingabe fortgesetzt werden darf
     */
    eingabe(ereignis) {
        switch (ereignis.type) {
            case INPUT_EVENT_TYPE.moveInputStarted: {
                this.brett.removeLegalMovesMarkers()
                const zuege = this.chess.moves({square: ereignis.squareFrom, verbose: true})
                this.brett.addLegalMovesMarkers(zuege)
                return zuege.length > 0
            }
            case INPUT_EVENT_TYPE.validateMoveInput: {
                this.brett.removeLegalMovesMarkers()
                const passend = this.chess.moves({square: ereignis.squareFrom, verbose: true})
                    .filter(zug => zug.to === ereignis.squareTo)
                if (passend.length === 0) {
                    return false
                }
                if (passend[0].promotion) {
                    this.brett.showPromotionDialog(ereignis.squareTo, this.farbe, ergebnis => {
                        if (ergebnis.type === PROMOTION_DIALOG_RESULT_TYPE.pieceSelected) {
                            this.spielerZug(ereignis.squareFrom, ereignis.squareTo, ergebnis.piece.charAt(1))
                        } else {
                            this.brett.setPosition(this.chess.fen(), true)
                            this.brett.enableMoveInput(this.eingabe, this.farbe)
                        }
                    })
                    return true
                }
                // Erst prüfen, wenn das Brett die eigene Zugdarstellung abgeschlossen hat
                ereignis.chessboard.state.moveInputProcess.then(() => {
                    this.spielerZug(ereignis.squareFrom, ereignis.squareTo)
                })
                return true
            }
            case INPUT_EVENT_TYPE.moveInputCanceled:
                this.brett.removeLegalMovesMarkers()
                return
            case INPUT_EVENT_TYPE.moveInputFinished:
                if (ereignis.legalMove) {
                    this.brett.disableMoveInput()
                }
                return
        }
    }

    /**
     * Prüft einen Zug des Spielers gegen die Lösung.
     *
     * Ein anderer als der erwartete Zug gilt ebenfalls als richtig, wenn er
     * matt setzt – wie bei Lichess, wo Aufgaben mehrere Mattzüge haben können.
     *
     * @param {string} von Ausgangsfeld
     * @param {string} nach Zielfeld
     * @param {string} [umwandlung] Figur bei Bauernumwandlung (q, r, b, n)
     */
    async spielerZug(von, nach, umwandlung) {
        this.brett.removeMarkers(MARKER_FALSCH)
        const zug = this.chess.move({from: von, to: nach, promotion: umwandlung})
        const uci = zug.from + zug.to + (zug.promotion || "")
        const matt = this.chess.isCheckmate()

        if (uci !== this.aufgabe.zuege[this.index] && !matt) {
            this.chess.undo()
            await this.brett.setPosition(this.chess.fen(), true)
            this.brett.addMarker(MARKER_FALSCH, nach)
            this.status(this.texte.falsch, "fehler")
            if (!this.fehlerGemacht) {
                this.fehlerGemacht = true
                this.melden(false)
            }
            this.brett.enableMoveInput(this.eingabe, this.farbe)
            return
        }

        this.index++
        await this.brett.setPosition(this.chess.fen(), true)
        this.zugMarkieren(zug)

        if (matt || this.index >= this.aufgabe.zuege.length) {
            this.abschliessen(this.fehlerGemacht ? this.texte.geloestMitFehler : this.texte.geloest, "erfolg")
            if (!this.fehlerGemacht) {
                this.melden(true)
            }
            return
        }

        this.status(this.texte.richtig, "erfolg")
        await warten(PAUSE)
        await this.gegnerZieht()
    }

    /**
     * Spielt die restliche Lösung vor. Wurde noch nichts gemeldet, zählt
     * die Aufgabe als nicht gelöst.
     */
    async loesungZeigen() {
        this.brett.disableMoveInput()
        this.brett.removeMarkers(MARKER_FALSCH)
        this.feld.loesung.disabled = true
        if (!this.fehlerGemacht) {
            this.fehlerGemacht = true
            this.melden(false)
        }
        while (this.index < this.aufgabe.zuege.length) {
            const zug = this.ziehen(this.aufgabe.zuege[this.index])
            this.index++
            await this.brett.setPosition(this.chess.fen(), true)
            this.zugMarkieren(zug)
            await warten(PAUSE)
        }
        this.abschliessen(this.texte.loesungGezeigt, "fehler")
    }

    /**
     * Beendet die Aufgabe: Eingabe sperren, Knöpfe umschalten, Angaben zur
     * Aufgabe zeigen, sobald die Antwort des Servers da ist.
     *
     * @param {string} text Abschlussmeldung
     * @param {string} art „erfolg" oder „fehler" für die Farbe der Meldung
     */
    abschliessen(text, art) {
        this.beendet = true
        this.brett.disableMoveInput()
        this.feld.loesung.disabled = true
        this.feld.naechste.hidden = false
        this.feld.naechste.focus()
        this.status(text, art)
        this.infoAnzeigen()
    }

    /**
     * Meldet das Ergebnis an den Server – höchstens einmal je Aufgabe.
     *
     * @param {boolean} geloest Ob die Aufgabe im ersten Anlauf gelöst wurde
     */
    async melden(geloest) {
        if (this.gemeldet) {
            return
        }
        this.gemeldet = true
        const aufgabe = this.aufgabe
        try {
            const antwort = await fetch(this.konfiguration.ergebnisUrl, {
                method: "POST",
                headers: {"Content-Type": "application/json", "Accept": "application/json", "X-Requested-With": "XMLHttpRequest"},
                credentials: "same-origin",
                body: JSON.stringify({id: aufgabe.id, geloest: geloest})
            })
            if (!antwort.ok) {
                throw new Error("HTTP " + antwort.status)
            }
            const ergebnis = await antwort.json()
            // Inzwischen eine neue Aufgabe geladen? Dann nur die Wertung aktualisieren.
            if (this.aufgabe === aufgabe) {
                this.ergebnis = ergebnis
            }
            this.spielerAnzeigen(ergebnis.spieler, ergebnis.differenz)
            this.infoAnzeigen()
        } catch (fehler) {
            console.error("Schachaufgaben:", fehler)
        }
    }

    /**
     * Zeigt Wertung, Differenz und Zähler des Spielers.
     *
     * @param {Object} spieler wertung, versuche, geloest, gast
     * @param {number} [differenz] Änderung durch die letzte Aufgabe
     */
    spielerAnzeigen(spieler, differenz) {
        this.feld.wertung.textContent = spieler.wertung
        this.feld.zaehler.textContent = this.texte.versuche.replace("%d", spieler.versuche).replace("%d", spieler.geloest)
        this.feld.gast.hidden = !spieler.gast
        if (differenz !== undefined) {
            this.feld.differenz.textContent = (differenz > 0 ? "+" : differenz < 0 ? "−" : "±") + Math.abs(differenz)
            this.feld.differenz.dataset.richtung = differenz > 0 ? "plus" : differenz < 0 ? "minus" : ""
        }
    }

    /**
     * Zeigt nach Ende der Aufgabe deren Wertung, Motive und Herkunft – erst
     * dann, weil die Motive sonst die Lösung verraten würden.
     */
    infoAnzeigen() {
        if (!this.beendet || !this.ergebnis) {
            return
        }
        const aufgabe = this.ergebnis.aufgabe
        const info = this.feld.info
        info.textContent = ""

        const zeile = (beschriftung, wert) => {
            const p = document.createElement("p")
            const b = document.createElement("strong")
            b.textContent = beschriftung + ": "
            p.append(b, wert)
            info.append(p)
        }

        zeile(this.texte.aufgabeWertung, String(aufgabe.wertung))
        if (aufgabe.motive.length > 0) {
            zeile(this.texte.motive, aufgabe.motive.map(motiv => this.konfiguration.motive[motiv] || motiv).join(", "))
        }
        if (aufgabe.eroeffnung) {
            zeile(this.texte.eroeffnung, eroeffnungUebersetzen(aufgabe.eroeffnung, this.konfiguration.eroeffnungen))
        }
        if (aufgabe.partieUrl) {
            const link = document.createElement("a")
            link.href = aufgabe.partieUrl
            link.textContent = this.texte.partie
            link.target = "_blank"
            link.rel = "noopener"
            const p = document.createElement("p")
            p.append(link)
            info.append(p)
        }
        info.hidden = false
        this.feld.bewertung.hidden = false
    }

    /**
     * Gibt die Stimme „Gefällt mir" (1) oder „Gefällt mir nicht" (-1) ab.
     * Ein zweiter Klick auf dieselbe Stimme nimmt sie zurück (0).
     *
     * @param {number} stimme 1 oder -1 je nach Knopf
     */
    async bewerten(stimme) {
        const neu = this.stimme === stimme ? 0 : stimme
        const aufgabe = this.aufgabe
        try {
            const antwort = await fetch(this.konfiguration.bewertungUrl, {
                method: "POST",
                headers: {"Content-Type": "application/json", "Accept": "application/json", "X-Requested-With": "XMLHttpRequest"},
                credentials: "same-origin",
                body: JSON.stringify({id: aufgabe.id, stimme: neu})
            })
            if (!antwort.ok) {
                throw new Error("HTTP " + antwort.status)
            }
            const ergebnis = await antwort.json()
            if (this.aufgabe === aufgabe) {
                this.stimmeAnzeigen(ergebnis.stimme)
            }
        } catch (fehler) {
            console.error("Schachaufgaben:", fehler)
        }
    }

    /**
     * Markiert den Knopf der abgegebenen Stimme.
     *
     * @param {number} stimme 1, -1 oder 0 für keine Stimme
     */
    stimmeAnzeigen(stimme) {
        this.stimme = stimme
        this.feld.stimmen.forEach(knopf => knopf.setAttribute("aria-pressed", String(Number(knopf.dataset.stimme) === stimme)))
    }

    /**
     * Führt einen UCI-Zug in chess.js aus.
     *
     * @param {string} uci Zug wie „e2e4" oder „e7e8q"
     * @returns {Object} Der Zug im Format von chess.js
     */
    ziehen(uci) {
        return this.chess.move({from: uci.substring(0, 2), to: uci.substring(2, 4), promotion: uci.charAt(4) || undefined})
    }

    /**
     * Hebt Ausgangs- und Zielfeld des letzten Zuges hervor.
     *
     * @param {Object} zug Der Zug im Format von chess.js
     */
    zugMarkieren(zug) {
        this.brett.removeMarkers(MARKER_ZUG)
        this.brett.addMarker(MARKER_ZUG, zug.from)
        this.brett.addMarker(MARKER_ZUG, zug.to)
    }

    /**
     * Setzt die Statusmeldung.
     *
     * @param {string} text Die Meldung
     * @param {string} [art] „erfolg", „fehler" oder leer
     */
    status(text, art = "") {
        this.feld.status.textContent = text
        this.feld.status.dataset.art = art
    }
}

document.querySelectorAll(".schachaufgaben-training").forEach(element => new SchachaufgabenTraining(element))
