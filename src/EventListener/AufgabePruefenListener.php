<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Schachbulle\ContaoSchachaufgabenBundle\Import\AufgabenPruefer;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Prüft Stellung und Züge, wenn eine Aufgabe im Backend gespeichert wird.
 *
 * Es gelten dieselben Regeln wie beim Import (AufgabenPruefer). Eine Ausnahme
 * im save_callback zeigt Contao als Fehlermeldung am Feld an und speichert
 * den Wert nicht.
 */
class AufgabePruefenListener
{
	/**
	 * Übernimmt den Prüfer und den Zugang zur Anfrage.
	 *
	 * @param AufgabenPruefer $pruefer      Prüft FEN und UCI-Zugfolge
	 * @param RequestStack    $requestStack Liefert das gesendete Formular
	 */
	public function __construct(private readonly AufgabenPruefer $pruefer, private readonly RequestStack $requestStack)
	{
	}

	/**
	 * Prüft die FEN.
	 *
	 * @param mixed $wert Eingabe aus dem Feld „fen"
	 *
	 * @throws \RuntimeException Mit deutscher Fehlerbeschreibung, wenn die FEN ungültig ist
	 *
	 * @return string Die FEN ohne Leerraum am Rand
	 */
	#[AsCallback(table: 'tl_schachaufgaben', target: 'fields.fen.save')]
	public function fen(mixed $wert): string
	{
		$fen = trim((string) $wert);
		$fehler = $this->pruefer->pruefeFen($fen);

		if (null !== $fehler) {
			throw new \RuntimeException($fehler);
		}

		return $fen;
	}

	/**
	 * Prüft die Zugfolge und vereinheitlicht die Leerzeichen.
	 *
	 * Ob der Spieler zuerst zieht, bestimmt die erlaubte Anzahl der Züge; der
	 * Wert kommt aus demselben Formular (siehe formularwert()).
	 *
	 * @param mixed         $wert Eingabe aus dem Feld „zuege"
	 * @param DataContainer $dc   Der Data Container mit dem bisherigen Datensatz
	 *
	 * @throws \RuntimeException Mit deutscher Fehlerbeschreibung, wenn die Züge ungültig sind
	 *
	 * @return string Die Züge, durch genau ein Leerzeichen getrennt
	 */
	#[AsCallback(table: 'tl_schachaufgaben', target: 'fields.zuege.save')]
	public function zuege(mixed $wert, DataContainer $dc): string
	{
		$zuege = self::zuegeVereinheitlichen((string) $wert);
		$fehler = $this->pruefer->pruefeZuege($zuege, '1' === $this->formularwert('spielerZuerst', $dc));

		if (null !== $fehler) {
			throw new \RuntimeException($fehler);
		}

		return $zuege;
	}

	/**
	 * Prüft „Spieler zieht zuerst" gegen die Zugfolge desselben Formulars.
	 *
	 * Contao speichert die Felder eines Formulars einzeln. Würde nur die
	 * Zugfolge geprüft, bliebe bei einem Fehler dort die neue Einstellung
	 * dieses Felds trotzdem gespeichert – und die Aufgabe wäre widersprüchlich
	 * (so im Test am 2026-09-28 geschehen). Deshalb prüft auch dieses Feld die
	 * Kombination und wird bei einem Widerspruch ebenfalls abgewiesen.
	 *
	 * @param mixed         $wert Eingabe aus dem Feld „spielerZuerst" ('1' oder '')
	 * @param DataContainer $dc   Der Data Container mit dem bisherigen Datensatz
	 *
	 * @throws \RuntimeException Mit deutscher Fehlerbeschreibung, wenn die Züge dazu nicht passen
	 *
	 * @return string Der unveränderte Wert
	 */
	#[AsCallback(table: 'tl_schachaufgaben', target: 'fields.spielerZuerst.save')]
	public function spielerZuerst(mixed $wert, DataContainer $dc): string
	{
		$zuege = self::zuegeVereinheitlichen($this->formularwert('zuege', $dc));

		// Unbrauchbare Züge meldet schon das Feld „zuege"; hier nur der Widerspruch
		if ('' !== $zuege && null === $this->pruefer->pruefeZuege($zuege, '1' !== (string) $wert) && null !== $this->pruefer->pruefeZuege($zuege, '1' === (string) $wert)) {
			throw new \RuntimeException('1' === (string) $wert
				? 'Passt nicht zur Zugfolge: Bei „Spieler zieht zuerst" muss die Anzahl der Züge ungerade sein.'
				: 'Passt nicht zur Zugfolge: Ohne „Spieler zieht zuerst" ist der erste Zug der Gegnerzug, die Anzahl der Züge muss gerade sein.');
		}

		return (string) $wert;
	}

	/**
	 * Liefert den Wert eines Felds aus dem gesendeten Formular.
	 *
	 * Ist das Feld dort nicht enthalten (etwa bei „Mehrere bearbeiten" mit
	 * anderer Feldauswahl), gilt der gespeicherte Wert.
	 *
	 * @param string        $feld Feldname
	 * @param DataContainer $dc   Der Data Container mit dem bisherigen Datensatz
	 *
	 * @return string Der gesendete bzw. gespeicherte Wert
	 */
	private function formularwert(string $feld, DataContainer $dc): string
	{
		$request = $this->requestStack->getCurrentRequest();

		if (null !== $request && $request->request->has($feld)) {
			return (string) $request->request->get($feld);
		}

		return (string) ($dc->activeRecord->{$feld} ?? '');
	}

	/**
	 * Trennt Züge durch genau ein Leerzeichen.
	 *
	 * @param string $zuege Züge mit beliebigem Leerraum
	 *
	 * @return string Die vereinheitlichte Zugfolge
	 */
	private static function zuegeVereinheitlichen(string $zuege): string
	{
		return implode(' ', preg_split('/\s+/', trim($zuege), -1, PREG_SPLIT_NO_EMPTY) ?: array());
	}
}
