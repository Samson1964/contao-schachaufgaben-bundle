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
use Schachbulle\ContaoSchachaufgabenBundle\Import\ImportLauf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Import der Lichess-Sammlung im Backend (do=schachaufgaben&key=import).
 *
 * Ablauf: Die entpackte CSV wird per FTP irgendwo unter files/ abgelegt. Die
 * Seite listet alle CSV-Dateien dort, nimmt Datei und Filter entgegen und
 * startet den Import. Danach ruft das Skript der Seite so lange „schritt" auf,
 * bis der Import fertig ist; jeder Aufruf arbeitet nur wenige Sekunden. Der
 * Zustand liegt in der Sitzung, ein unterbrochener Import lässt sich deshalb
 * fortsetzen.
 *
 * Die Klasse ist ein öffentlicher Dienst; Contao holt sie über
 * System::importStatic() aus dem Container, wenn der key-Callback aus
 * $GLOBALS['BE_MOD'] aufgerufen wird.
 */
class ImportSeite
{
	private const SITZUNG = 'schachaufgaben_import';

	/**
	 * Zeitbudget je Häppchen in Sekunden. Deutlich unter den üblichen
	 * 30 Sekunden max_execution_time, damit auch ein langsamer Datenbankblock
	 * am Ende noch hineinpasst.
	 */
	private const SEKUNDEN = 8.0;

	private ImportLauf $lauf;

	private RequestStack $requestStack;

	private ContaoCsrfTokenManager $tokenManager;

	private string $tokenName;

	private string $dateiVerzeichnis;

	private string $projektVerzeichnis;

	/**
	 * Übernimmt Dienste und Pfade.
	 *
	 * @param ImportLauf             $lauf               Verarbeitet die Häppchen
	 * @param RequestStack           $requestStack       Liefert Anfrage und Sitzung
	 * @param ContaoCsrfTokenManager $tokenManager       Prüft den Request-Token
	 * @param string                 $tokenName          Name des Contao-Tokens
	 * @param string                 $projektVerzeichnis Wurzel der Installation
	 * @param string                 $uploadPfad         Dateiverwaltung relativ zur Wurzel, meist „files"
	 */
	public function __construct(ImportLauf $lauf, RequestStack $requestStack, ContaoCsrfTokenManager $tokenManager, string $tokenName, string $projektVerzeichnis, string $uploadPfad)
	{
		$this->lauf = $lauf;
		$this->requestStack = $requestStack;
		$this->tokenManager = $tokenManager;
		$this->tokenName = $tokenName;
		$this->projektVerzeichnis = rtrim(str_replace('\\', '/', $projektVerzeichnis), '/');
		$this->dateiVerzeichnis = $this->projektVerzeichnis.'/'.trim($uploadPfad, '/');
	}

	/**
	 * Einstieg aus dem Backend: zeigt das Formular oder beantwortet einen
	 * AJAX-Aufruf des Skripts.
	 *
	 * AJAX-Aufrufe enden mit einer ResponseException, die Contao als
	 * JSON-Antwort ausliefert, ohne die Backend-Seite drumherum zu bauen.
	 * Das Skript sendet sie als gewöhnliches POST ohne „X-Requested-With",
	 * weil das Backend solche Anfragen sonst an seine eigene Ajax-Klasse
	 * weiterreicht. Den Request-Token prüft damit Contao; die Prüfung hier
	 * bleibt als zweite Sicherung, falls sich dieses Verhalten ändert.
	 *
	 * @return string Das HTML der Importseite
	 */
	public function ausfuehren(): string
	{
		System::loadLanguageFile('tl_schachaufgaben');
		$request = $this->requestStack->getCurrentRequest();

		if (null !== $request && $request->isMethod('POST') && $request->request->has('aktion')) {
			throw new ResponseException($this->ajax($request));
		}

		$zustand = null === $request ? null : $request->getSession()->get(self::SITZUNG);

		$template = new BackendTemplate('be_schachaufgaben_import');
		$template->texte = $GLOBALS['TL_LANG']['tl_schachaufgaben']['import_seite'] ?? array();
		$template->dateien = $this->dateien();
		$template->uploadPfad = substr($this->dateiVerzeichnis, \strlen($this->projektVerzeichnis) + 1);
		$template->token = $this->tokenManager->getDefaultTokenValue();
		$template->adresse = null === $request ? '' : $request->getRequestUri();
		$template->zurueck = null === $request ? '' : $request->getBaseUrl().$request->getPathInfo().'?do=schachaufgaben';
		$template->offen = \is_array($zustand) && !$zustand['fertig'] ? $this->zusammenfassung($zustand) : null;

		return $template->parse();
	}

	/**
	 * Beantwortet die AJAX-Aufrufe „start", „schritt" und „verwerfen".
	 *
	 * @param Request $request Die Anfrage mit aktion, REQUEST_TOKEN und
	 *                         bei „start" den Formularwerten
	 *
	 * @return JsonResponse Zusammenfassung des Zustands oder eine Fehlermeldung
	 */
	private function ajax(Request $request): JsonResponse
	{
		$token = new CsrfToken($this->tokenName, (string) $request->request->get('REQUEST_TOKEN'));

		if (!$this->tokenManager->isTokenValid($token)) {
			return new JsonResponse(array('fehler' => 'Ungültiger Request-Token. Bitte die Seite neu laden.'), 403);
		}

		$session = $request->getSession();

		try {
			switch ($request->request->get('aktion')) {
				case 'start':
					$zustand = $this->lauf->beginnen($this->dateiPruefen((string) $request->request->get('datei')), $this->optionen($request));
					break;

				case 'schritt':
					$zustand = $session->get(self::SITZUNG);

					if (!\is_array($zustand)) {
						return new JsonResponse(array('fehler' => 'Es läuft kein Import.'), 409);
					}

					@set_time_limit((int) (self::SEKUNDEN * 4));
					$zustand = $this->lauf->schritt($zustand, self::SEKUNDEN);
					break;

				case 'verwerfen':
					$session->remove(self::SITZUNG);

					return new JsonResponse(array('verworfen' => true));

				default:
					return new JsonResponse(array('fehler' => 'Unbekannte Aktion.'), 400);
			}
		} catch (\RuntimeException $e) {
			return new JsonResponse(array('fehler' => $e->getMessage()), 422);
		}

		$session->set(self::SITZUNG, $zustand);

		return new JsonResponse($this->zusammenfassung($zustand));
	}

	/**
	 * Bereitet den Zustand für die Anzeige auf.
	 *
	 * @param array<string, mixed> $zustand Der Importzustand
	 *
	 * @return array<string, mixed> Datei, Fortschritt, Zähler, Fehlerbeispiele
	 *                              und die bisherige Laufzeit in Sekunden
	 */
	private function zusammenfassung(array $zustand): array
	{
		return array(
			'datei'    => substr((string) $zustand['pfad'], \strlen($this->projektVerzeichnis) + 1),
			'prozent'  => $this->lauf->prozent($zustand),
			'fertig'   => (bool) $zustand['fertig'],
			'zaehler'  => $zustand['zaehler'],
			'fehler'   => $zustand['fehler'],
			'sekunden' => time() - (int) $zustand['beginn'],
		);
	}

	/**
	 * Liest die Filter und Schalter aus dem Formular.
	 *
	 * @param Request $request Die Anfrage „start"
	 *
	 * @return array<string, mixed> Optionen im Format von ImportLauf::beginnen()
	 */
	private function optionen(Request $request): array
	{
		$zahl = static function (string $feld, int $standard) use ($request): int {
			$wert = trim((string) $request->request->get($feld));

			return is_numeric($wert) ? (int) $wert : $standard;
		};

		$motive = preg_split('/[\s,]+/', trim((string) $request->request->get('motive')), -1, PREG_SPLIT_NO_EMPTY) ?: array();

		return array(
			'minBeliebtheit'     => $zahl('minBeliebtheit', -100),
			'minSpiele'          => $zahl('minSpiele', 0),
			'minWertung'         => $zahl('minWertung', 0),
			'maxWertung'         => $zahl('maxWertung', 9999),
			'motive'             => $motive,
			'limit'              => max(0, $zahl('limit', 0)),
			'aktualisieren'      => '1' === $request->request->get('aktualisieren'),
			'wertungUebernehmen' => '1' === $request->request->get('wertungUebernehmen'),
			'veroeffentlichen'   => '1' !== $request->request->get('unveroeffentlicht'),
		);
	}

	/**
	 * Stellt sicher, dass die gewählte Datei eine der angebotenen ist.
	 *
	 * Verglichen wird mit der Liste aus dateien(), nicht mit dem Dateisystem:
	 * So lässt sich mit „../" nichts außerhalb der Dateiverwaltung öffnen.
	 *
	 * @param string $relativ Pfad relativ zur Wurzel der Installation
	 *
	 * @throws \RuntimeException Wenn die Datei nicht in der Liste steht
	 *
	 * @return string Der absolute Pfad
	 */
	private function dateiPruefen(string $relativ): string
	{
		foreach ($this->dateien() as $datei) {
			if ($datei['pfad'] === $relativ) {
				return $this->projektVerzeichnis.'/'.$relativ;
			}
		}

		throw new \RuntimeException('Bitte eine der angebotenen Dateien wählen.');
	}

	/**
	 * Sucht alle CSV-Dateien in der Dateiverwaltung.
	 *
	 * @return array<int, array{pfad: string, groesse: int}> Pfade relativ zur
	 *                                                       Wurzel, alphabetisch
	 */
	private function dateien(): array
	{
		if (!is_dir($this->dateiVerzeichnis)) {
			return array();
		}

		$dateien = array();
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($this->dateiVerzeichnis, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::LEAVES_ONLY,
			\RecursiveIteratorIterator::CATCH_GET_CHILD
		);

		foreach ($iterator as $datei) {
			if ($datei->isFile() && 'csv' === strtolower($datei->getExtension())) {
				$pfad = str_replace('\\', '/', $datei->getPathname());
				$dateien[] = array('pfad' => substr($pfad, \strlen($this->projektVerzeichnis) + 1), 'groesse' => (int) $datei->getSize());
			}
		}

		usort($dateien, static function (array $a, array $b): int {
			return strcmp($a['pfad'], $b['pfad']);
		});

		return $dateien;
	}
}
