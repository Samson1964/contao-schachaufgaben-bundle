<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Controller;

use Contao\FrontendUser;
use Schachbulle\ContaoSchachaufgabenBundle\Training\Training;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * JSON-Schnittstelle des Trainings für das Frontend-Modul.
 *
 * Die Routen stehen in Resources/config/routes.yaml mit _scope: frontend,
 * damit die Firewall des Frontends greift und angemeldete Mitglieder erkannt
 * werden. Antworten werden nie zwischengespeichert.
 *
 * Schutz vor fremden Formularen (CSRF): Das Ergebnis wird nur als
 * application/json angenommen. Ein Formular einer fremden Seite kann diesen
 * Typ nicht senden, und ein Skript einer fremden Seite bräuchte dafür die
 * CORS-Freigabe, die es hier nicht gibt. Nach derselben Überlegung lässt
 * Contao selbst solche Anfragen ohne Request-Token passieren.
 */
class TrainingController
{
	private Training $training;

	private TokenStorageInterface $tokenStorage;

	/**
	 * Übernimmt die benötigten Dienste.
	 *
	 * @param Training              $training     Der Ablauf des Trainings
	 * @param TokenStorageInterface $tokenStorage Liefert das angemeldete Mitglied
	 */
	public function __construct(Training $training, TokenStorageInterface $tokenStorage)
	{
		$this->training = $training;
		$this->tokenStorage = $tokenStorage;
	}

	/**
	 * Stellt die nächste Aufgabe (GET).
	 *
	 * @param Request $request Die Anfrage; ihre Sitzung wird bei Gästen gestartet
	 *
	 * @return JsonResponse Die Aufgabe, oder 404 mit „fertig: true", wenn der
	 *                      Spieler alle Aufgaben schon hatte
	 */
	public function aufgabe(Request $request): JsonResponse
	{
		$daten = $this->training->aufgabeStellen($this->memberId(), $request->getSession());

		if (null === $daten) {
			return $this->antwort(array('fertig' => true), 404);
		}

		return $this->antwort($daten);
	}

	/**
	 * Nimmt das Ergebnis einer Aufgabe entgegen (POST, JSON {id, geloest}).
	 *
	 * @param Request $request Die Anfrage
	 *
	 * @return JsonResponse Die neue Wertung; 415 bei falschem Inhaltstyp,
	 *                      400 bei unvollständigen Daten, 409 wenn die Aufgabe
	 *                      nicht gestellt oder schon gewertet wurde
	 */
	public function ergebnis(Request $request): JsonResponse
	{
		if (0 !== strpos((string) $request->headers->get('Content-Type'), 'application/json')) {
			return $this->antwort(array('fehler' => 'Erwartet wird application/json.'), 415);
		}

		$daten = json_decode((string) $request->getContent(), true);

		if (!\is_array($daten) || !isset($daten['id'], $daten['geloest']) || !\is_int($daten['id']) || !\is_bool($daten['geloest'])) {
			return $this->antwort(array('fehler' => 'Erwartet werden id (Zahl) und geloest (true/false).'), 400);
		}

		$ergebnis = $this->training->ergebnisWerten($this->memberId(), $request->getSession(), $daten['id'], $daten['geloest']);

		if (null === $ergebnis) {
			return $this->antwort(array('fehler' => 'Diese Aufgabe ist nicht offen.'), 409);
		}

		return $this->antwort($ergebnis);
	}

	/**
	 * Nimmt eine Bewertung entgegen (POST, JSON {id, stimme}).
	 *
	 * @param Request $request Die Anfrage
	 *
	 * @return JsonResponse Stimme und Zähler der Aufgabe; 415 bei falschem
	 *                      Inhaltstyp, 400 bei unvollständigen Daten, 409 wenn
	 *                      die Aufgabe (noch) nicht bewertet werden darf
	 */
	public function bewertung(Request $request): JsonResponse
	{
		if (!str_starts_with((string) $request->headers->get('Content-Type'), 'application/json')) {
			return $this->antwort(array('fehler' => 'Erwartet wird application/json.'), 415);
		}

		$daten = json_decode((string) $request->getContent(), true);

		if (!\is_array($daten) || !\is_int($daten['id'] ?? null) || !\in_array($daten['stimme'] ?? null, array(-1, 0, 1), true)) {
			return $this->antwort(array('fehler' => 'Erwartet werden id (Zahl) und stimme (1, 0 oder -1).'), 400);
		}

		$ergebnis = $this->training->bewerten($this->memberId(), $request->getSession(), $daten['id'], $daten['stimme']);

		if (null === $ergebnis) {
			return $this->antwort(array('fehler' => 'Diese Aufgabe kann (noch) nicht bewertet werden.'), 409);
		}

		return $this->antwort($ergebnis);
	}

	/**
	 * Ermittelt das angemeldete Mitglied.
	 *
	 * @return int|null Die ID aus tl_member, oder null für Gäste (auch wenn
	 *                  nur ein Backend-Benutzer angemeldet ist)
	 */
	private function memberId(): ?int
	{
		$token = $this->tokenStorage->getToken();
		$user = null === $token ? null : $token->getUser();

		return $user instanceof FrontendUser ? (int) $user->id : null;
	}

	/**
	 * Baut eine JSON-Antwort, die weder Browser noch Proxy speichern dürfen.
	 *
	 * @param array<string, mixed> $daten  Der Inhalt
	 * @param int                  $status HTTP-Status
	 *
	 * @return JsonResponse Die Antwort
	 */
	private function antwort(array $daten, int $status = 200): JsonResponse
	{
		$antwort = new JsonResponse($daten, $status);
		$antwort->setPrivate();
		$antwort->headers->addCacheControlDirective('no-store');

		return $antwort;
	}
}
