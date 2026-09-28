<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Backend;

use Contao\BackendTemplate;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\System;
use Doctrine\DBAL\Connection;
use Schachbulle\ContaoSchachaufgabenBundle\Import\AufgabenPruefer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Import eigener Aufgaben aus einer PGN-Datei (do=schachaufgaben&key=pgn).
 *
 * Die PGN wird im Browser gelesen und mit chess.js in das Format des Trainings
 * umgerechnet (pgn.js): Kurzschreibweise in UCI umzurechnen braucht einen
 * Zuggenerator, den es nur dort gibt. Der Server bekommt fertige Aufgaben,
 * prüft sie mit denselben Regeln wie beim Lichess-Import und speichert sie.
 * Dubletten erkennt er an einer Prüfsumme aus FEN und Zügen (Feld
 * „schluessel"); dieselbe Datei lässt sich daher gefahrlos erneut importieren.
 *
 * Öffentlicher Dienst, weil Contao den key-Callback über
 * System::importStatic() aus dem Container holt.
 */
class PgnImportSeite
{
	/**
	 * Höchstzahl Aufgaben je Anfrage; das Skript schickt größere Dateien in
	 * mehreren Blöcken.
	 */
	public const BLOCK = 200;

	/**
	 * Übernimmt Dienste und Pfade.
	 *
	 * @param Connection             $connection   Die Datenbankverbindung von Contao
	 * @param AufgabenPruefer        $pruefer      Prüft FEN und Zugfolge
	 * @param RequestStack           $requestStack Liefert die Anfrage
	 * @param ContaoCsrfTokenManager $tokenManager Prüft den Request-Token
	 * @param string                 $tokenName    Name des Contao-Tokens
	 */
	public function __construct(
		private readonly Connection $connection,
		private readonly AufgabenPruefer $pruefer,
		private readonly RequestStack $requestStack,
		private readonly ContaoCsrfTokenManager $tokenManager,
		private readonly string $tokenName,
	) {
	}

	/**
	 * Einstieg aus dem Backend: zeigt das Formular oder speichert einen Block.
	 *
	 * Der Block kommt als gewöhnliches POST ohne „X-Requested-With" (sonst
	 * reicht das Backend die Anfrage an seine eigene Ajax-Klasse weiter) und
	 * endet mit einer JSON-Antwort über eine ResponseException.
	 *
	 * @return string Das HTML der Importseite
	 */
	public function ausfuehren(): string
	{
		System::loadLanguageFile('tl_schachaufgaben');
		$request = $this->requestStack->getCurrentRequest();

		if (null !== $request && $request->isMethod('POST') && 'speichern' === $request->request->get('aktion')) {
			throw new ResponseException($this->speichernAntwort($request));
		}

		$basis = (null === $request ? '' : $request->getBasePath()).'/bundles/contaoschachaufgaben/';
		$version = substr(md5((string) @filemtime(__DIR__.'/../Resources/public/pgn.js')), 0, 8);

		$template = new BackendTemplate('be_schachaufgaben_pgnimport');
		$template->texte = $GLOBALS['TL_LANG']['tl_schachaufgaben']['pgn_seite'] ?? array();
		$template->token = $this->tokenManager->getDefaultTokenValue();
		$template->adresse = null === $request ? '' : $request->getRequestUri();
		$template->zurueck = null === $request ? '' : $request->getBaseUrl().$request->getPathInfo().'?do=schachaufgaben';
		$template->pgnSkript = $basis.'pgn.js?v='.$version;
		$template->chessSkript = $basis.'vendor/chess.js/chess.js';
		$template->block = self::BLOCK;

		return $template->parse();
	}

	/**
	 * Speichert einen Block von Aufgaben.
	 *
	 * @param array<int, mixed> $aufgaben         Aufgaben aus pgn.js: fen, zuege (Liste),
	 *                                            spielerZuerst, motive, wertung, titel
	 * @param int               $standardWertung  Wertung für Aufgaben ohne eigene Angabe
	 * @param string            $standardMotive   Motive für Aufgaben ohne eigene Angabe
	 * @param bool              $veroeffentlichen Neue Aufgaben sofort veröffentlichen
	 *
	 * @return array{neu: int, doppelt: int, fehlerhaft: int, fehler: array<int, string>}
	 *               Zähler und bis zu zwanzig Fehlermeldungen mit Titel der Partie
	 */
	public function speichern(array $aufgaben, int $standardWertung, string $standardMotive, bool $veroeffentlichen): array
	{
		$ergebnis = array('neu' => 0, 'doppelt' => 0, 'fehlerhaft' => 0, 'fehler' => array());
		$jetzt = time();

		foreach (\array_slice($aufgaben, 0, self::BLOCK) as $aufgabe) {
			$aufgabe = \is_array($aufgabe) ? $aufgabe : array();
			$fen = trim((string) ($aufgabe['fen'] ?? ''));
			$zuege = implode(' ', array_map('strval', (array) ($aufgabe['zuege'] ?? array())));
			$spielerZuerst = true === ($aufgabe['spielerZuerst'] ?? null);
			$titel = mb_substr(trim((string) ($aufgabe['titel'] ?? '')), 0, 80);

			$fehler = $this->pruefer->pruefeFen($fen) ?? $this->pruefer->pruefeZuege($zuege, $spielerZuerst);

			if (null !== $fehler) {
				++$ergebnis['fehlerhaft'];

				if (\count($ergebnis['fehler']) < 20) {
					$ergebnis['fehler'][] = ('' !== $titel ? $titel.': ' : '').$fehler;
				}

				continue;
			}

			$wertung = isset($aufgabe['wertung']) && is_numeric($aufgabe['wertung']) ? (int) $aufgabe['wertung'] : $standardWertung;
			$motive = trim((string) ($aufgabe['motive'] ?? '')) ?: $standardMotive;

			$geschrieben = $this->connection->executeStatement(
				"INSERT IGNORE INTO tl_schachaufgaben
				 (tstamp, quelle, published, fen, zuege, spielerZuerst, motive, wertung, wertungAbweichung, schluessel)
				 VALUES (?, 'pgn', ?, ?, ?, ?, ?, ?, 350, ?)",
				array(
					$jetzt,
					$veroeffentlichen ? '1' : '',
					$fen,
					$zuege,
					$spielerZuerst ? '1' : '',
					self::motiveBereinigen($motive),
					max(400, min(3500, $wertung)),
					sha1($fen.'|'.$zuege),
				)
			);

			++$ergebnis[1 === (int) $geschrieben ? 'neu' : 'doppelt'];
		}

		return $ergebnis;
	}

	/**
	 * Lässt von den Motiven nur Wörter aus Buchstaben und Ziffern übrig.
	 *
	 * @param string $motive Motive, durch Leerzeichen oder Kommas getrennt
	 *
	 * @return string Höchstens 255 Zeichen, durch Leerzeichen getrennt
	 */
	public static function motiveBereinigen(string $motive): string
	{
		$woerter = preg_split('/[\s,;]+/', $motive, -1, PREG_SPLIT_NO_EMPTY) ?: array();
		$woerter = array_filter($woerter, static fn (string $wort): bool => 1 === preg_match('/^[A-Za-z0-9]+$/', $wort));

		return mb_substr(implode(' ', array_unique($woerter)), 0, 255);
	}

	/**
	 * Prüft den Request-Token und beantwortet die Anfrage „speichern".
	 *
	 * @param Request $request Die Anfrage mit REQUEST_TOKEN, aufgaben (JSON),
	 *                         wertung, motive und unveroeffentlicht
	 *
	 * @return JsonResponse Das Ergebnis aus speichern() oder eine Fehlermeldung
	 */
	private function speichernAntwort(Request $request): JsonResponse
	{
		if (!$this->tokenManager->isTokenValid(new CsrfToken($this->tokenName, (string) $request->request->get('REQUEST_TOKEN')))) {
			return new JsonResponse(array('fehler' => 'Ungültiger Request-Token. Bitte die Seite neu laden.'), 403);
		}

		$aufgaben = json_decode((string) $request->request->get('aufgaben'), true);

		if (!\is_array($aufgaben)) {
			return new JsonResponse(array('fehler' => 'Es wurden keine Aufgaben übertragen.'), 400);
		}

		$wertung = trim((string) $request->request->get('wertung'));

		return new JsonResponse($this->speichern(
			$aufgaben,
			is_numeric($wertung) ? (int) $wertung : 1500,
			(string) $request->request->get('motive'),
			'1' !== $request->request->get('unveroeffentlicht')
		));
	}
}
