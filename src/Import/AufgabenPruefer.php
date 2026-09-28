<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Import;

/**
 * Prüft Stellung und Zugfolge einer Aufgabe auf formale Gültigkeit.
 *
 * Geprüft wird nur die Form, nicht die Legalität der Züge: dafür wäre ein
 * vollständiger Zuggenerator nötig. Die Lichess-Aufgaben sind von Stockfish
 * erzeugt und damit legal; die Prüfung fängt beschädigte Zeilen und
 * Tippfehler bei eigenen Aufgaben ab.
 */
class AufgabenPruefer
{
	/**
	 * Prüft, ob eine FEN formal gültig ist.
	 *
	 * Verlangt werden alle sechs Felder: Brett, Zugrecht, Rochaderechte,
	 * En-passant-Feld, Halbzugzähler und Zugnummer. Jede der acht Reihen
	 * muss genau acht Felder ergeben, und jede Seite genau einen König haben.
	 *
	 * @param string $fen Die zu prüfende FEN; führende und folgende
	 *                    Leerzeichen werden ignoriert
	 *
	 * @return string|null null bei gültiger FEN, sonst eine deutsche
	 *                     Fehlerbeschreibung
	 */
	public function pruefeFen(string $fen): ?string
	{
		$felder = preg_split('/\s+/', trim($fen));

		if (6 !== \count($felder)) {
			return 'Die FEN muss aus sechs durch Leerzeichen getrennten Feldern bestehen.';
		}

		[$brett, $zugrecht, $rochade, $enPassant, $halbzuege, $zugnummer] = $felder;

		$reihen = explode('/', $brett);

		if (8 !== \count($reihen)) {
			return 'Das Brett muss aus acht Reihen bestehen.';
		}

		foreach ($reihen as $nummer => $reihe) {
			if (!preg_match('/^[pnbrqkPNBRQK1-8]+$/', $reihe)) {
				return sprintf('Reihe %d enthält ungültige Zeichen.', 8 - $nummer);
			}

			// Ziffern stehen für so viele leere Felder, Buchstaben für je eine Figur
			$breite = 0;

			foreach (str_split($reihe) as $zeichen) {
				$breite += ctype_digit($zeichen) ? (int) $zeichen : 1;
			}

			if (8 !== $breite) {
				return sprintf('Reihe %d hat %d statt 8 Felder.', 8 - $nummer, $breite);
			}
		}

		if (1 !== substr_count($brett, 'K') || 1 !== substr_count($brett, 'k')) {
			return 'Jede Seite muss genau einen König haben.';
		}

		if ('w' !== $zugrecht && 'b' !== $zugrecht) {
			return 'Das Zugrecht muss „w" oder „b" sein.';
		}

		if (!preg_match('/^(-|K?Q?k?q?)$/', $rochade) || '' === $rochade) {
			return 'Die Rochaderechte sind ungültig.';
		}

		// Das En-passant-Feld liegt immer auf der dritten oder sechsten Reihe
		if (!preg_match('/^(-|[a-h][36])$/', $enPassant)) {
			return 'Das En-passant-Feld ist ungültig.';
		}

		if (!ctype_digit($halbzuege) || !ctype_digit($zugnummer) || 0 === (int) $zugnummer) {
			return 'Halbzugzähler und Zugnummer müssen Zahlen sein, die Zugnummer mindestens 1.';
		}

		return null;
	}

	/**
	 * Prüft eine Zugfolge in UCI-Schreibweise.
	 *
	 * Jede Aufgabe endet mit einem Zug des Spielers. Im Lichess-Format beginnt
	 * sie mit dem auslösenden Gegnerzug; dann sind es mindestens zwei Züge und
	 * immer eine gerade Anzahl. Zieht der Spieler zuerst (etwa bei Aufgaben aus
	 * einer PGN-Sammlung), genügt ein Zug, und die Anzahl ist ungerade.
	 *
	 * @param string $zuege         Züge wie „e2e4 e7e8q", durch Leerzeichen getrennt
	 * @param bool   $spielerZuerst true, wenn der erste Zug schon der Lösungszug ist
	 *
	 * @return string|null null bei gültiger Zugfolge, sonst eine deutsche
	 *                     Fehlerbeschreibung
	 */
	public function pruefeZuege(string $zuege, bool $spielerZuerst = false): ?string
	{
		$liste = preg_split('/\s+/', trim($zuege), -1, PREG_SPLIT_NO_EMPTY) ?: array();

		if ($spielerZuerst) {
			if (0 === \count($liste)) {
				return 'Es wird mindestens ein Lösungszug benötigt.';
			}

			if (1 !== \count($liste) % 2) {
				return 'Die Anzahl der Züge muss ungerade sein: Der Spieler zieht zuerst, und die Aufgabe endet mit seinem Zug.';
			}
		} else {
			if (\count($liste) < 2) {
				return 'Es werden mindestens zwei Züge benötigt: der Gegnerzug und ein Lösungszug.';
			}

			if (0 !== \count($liste) % 2) {
				return 'Die Anzahl der Züge muss gerade sein, weil die Aufgabe mit einem Zug des Spielers endet.';
			}
		}

		foreach ($liste as $nummer => $zug) {
			// Ausgangsfeld, Zielfeld und bei Umwandlung die neue Figur
			if (!preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/', $zug)) {
				return sprintf('Zug %d („%s") ist keine gültige UCI-Schreibweise.', $nummer + 1, $zug);
			}
		}

		return null;
	}
}
