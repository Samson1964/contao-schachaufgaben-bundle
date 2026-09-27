<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Module;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\Date;
use Contao\Module;
use Contao\System;
use Schachbulle\ContaoSchachaufgabenBundle\Training\Anzeigename;

/**
 * Frontend-Modul „Schachaufgaben-Rangliste": die Mitglieder mit der höchsten
 * aktuellen Wertung.
 *
 * Aufgeführt werden nur Mitglieder mit einer Mindestzahl gespielter Aufgaben,
 * weil die Wertung vorher zu unsicher ist. Gezeigt wird der Name als
 * „Vorname N.", damit die Rangliste keine vollen Namen veröffentlicht. Das
 * angemeldete Mitglied wird hervorgehoben und, falls es nicht unter den
 * gezeigten Plätzen ist, mit seinem Platz unter der Tabelle angehängt.
 *
 * Die ewige Bestenliste (BestenlisteModule) erbt von dieser Klasse und ändert
 * nur Sortierung, Aufnahmebedingung und Template.
 */
class RanglisteModule extends Module
{
	/**
	 * @var string
	 */
	protected $strTemplate = 'mod_schachaufgaben_rangliste';

	/**
	 * Gibt im Backend nur einen Platzhalter aus, im Frontend das Modul.
	 *
	 * @return string Das HTML des Moduls
	 */
	public function generate()
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if (null !== $request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
			$template = new BackendTemplate('be_wildcard');
			$template->wildcard = '### '.mb_strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0] ?? $this->type).' ###';
			$template->title = $this->headline;
			$template->id = $this->id;
			$template->link = $this->name;
			$template->href = 'contao?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

			return $template->parse();
		}

		return parent::generate();
	}

	/**
	 * Liest die Liste aus der Datenbank.
	 *
	 * Gesperrte oder abgelaufene Mitglieder (disable, stop) werden nicht
	 * aufgeführt. Die Anzahl der Plätze kommt aus schachaufgabenPlaetze.
	 */
	protected function compile(): void
	{
		System::loadLanguageFile('default');

		$GLOBALS['TL_CSS'][] = 'bundles/contaoschachaufgaben/training.css';

		$anzahl = (int) $this->schachaufgabenPlaetze > 0 ? (int) $this->schachaufgabenPlaetze : 20;
		$db = System::getContainer()->get('database_connection');
		[$bedingung, $parameter] = $this->bedingung();
		$spalte = $this->sortierspalte();
		$felder = 's.wertung, s.versuche, s.geloest, s.bestWertung, s.bestDatum, m.firstname, m.lastname, m.username';

		$zeilen = $db->fetchAllAssociative(
			sprintf(
				'SELECT %s FROM tl_schachaufgaben_spieler s INNER JOIN tl_member m ON m.id = s.memberId
				 WHERE %s ORDER BY %s DESC, s.versuche DESC LIMIT %d',
				$felder,
				$bedingung,
				$spalte,
				$anzahl
			),
			$parameter
		);

		// Der TokenChecker ist in beiden Fassungen ein öffentlicher Dienst
		$eigenerName = System::getContainer()->get('contao.security.token_checker')->getFrontendUsername();
		$liste = array();
		$gefunden = false;

		foreach ($zeilen as $index => $zeile) {
			$eintrag = $this->eintrag($zeile, $index + 1, $eigenerName);
			$gefunden = $gefunden || $eintrag['eigene'];
			$liste[] = $eintrag;
		}

		$eigene = null;

		if (null !== $eigenerName && !$gefunden) {
			$zeile = $db->fetchAssociative(
				sprintf('SELECT %s FROM tl_schachaufgaben_spieler s INNER JOIN tl_member m ON m.id = s.memberId WHERE m.username = ?', $felder),
				array($eigenerName)
			);

			if (false !== $zeile) {
				$fehlen = $this->fehlen($zeile);
				$platz = null;

				if (0 === $fehlen) {
					$platz = 1 + (int) $db->fetchOne(
						sprintf('SELECT COUNT(*) FROM tl_schachaufgaben_spieler s INNER JOIN tl_member m ON m.id = s.memberId WHERE %s AND %s > ?', $bedingung, $spalte),
						array_merge($parameter, array($zeile[substr($spalte, 2)]))
					);
				}

				$eigene = array('fehlen' => $fehlen) + $this->eintrag($zeile, $platz, $eigenerName);
			}
		}

		$this->Template->liste = $liste;
		$this->Template->eigene = $eigene;
		// Leerzeile nur, wenn der eigene Platz nicht unmittelbar an die Liste anschließt
		$this->Template->luecke = null !== $eigene && (null === $eigene['platz'] || $eigene['platz'] > \count($liste) + 1);
		$this->Template->minVersuche = max(0, (int) $this->schachaufgabenMinVersuche);
		$this->Template->texte = $GLOBALS['TL_LANG']['MSC']['schachaufgaben'] ?? array();
	}

	/**
	 * Spalte, nach der die Liste absteigend sortiert wird.
	 *
	 * @return string Spaltenname mit Tabellenkürzel „s."
	 */
	protected function sortierspalte(): string
	{
		return 's.wertung';
	}

	/**
	 * Bedingung dafür, in der Liste zu stehen.
	 *
	 * @return array{0: string, 1: array<int, mixed>} SQL-Bedingung und ihre Parameter
	 */
	protected function bedingung(): array
	{
		return array(
			"s.versuche >= ? AND m.disable = '' AND (m.stop = '' OR m.stop > ?)",
			array(max(0, (int) $this->schachaufgabenMinVersuche), time()),
		);
	}

	/**
	 * Wie viele Aufgaben dem Mitglied noch fehlen, um in der Liste zu stehen.
	 *
	 * @param array<string, mixed> $zeile Die Zeile des angemeldeten Mitglieds
	 *
	 * @return int 0, wenn es schon in die Liste gehört
	 */
	protected function fehlen(array $zeile): int
	{
		return max(0, (int) $this->schachaufgabenMinVersuche - (int) $zeile['versuche']);
	}

	/**
	 * Bereitet eine Zeile für das Template auf.
	 *
	 * @param array<string, mixed> $zeile       Zeile aus tl_schachaufgaben_spieler mit Mitgliedsdaten
	 * @param int|null             $platz       Platz in der Liste, oder null solange noch nicht gewertet
	 * @param string|null          $eigenerName Benutzername des angemeldeten Mitglieds, oder null
	 *
	 * @return array<string, mixed> platz, name („Vorname N."), wertung, versuche, quote,
	 *                              bestWertung, bestDatum (formatiert oder leer) und eigene
	 */
	protected function eintrag(array $zeile, ?int $platz, ?string $eigenerName): array
	{
		return array(
			'platz'       => $platz,
			'name'        => Anzeigename::kurz($zeile['firstname'], $zeile['lastname']),
			'wertung'     => (int) round((float) $zeile['wertung']),
			'versuche'    => (int) $zeile['versuche'],
			'quote'       => (int) $zeile['versuche'] > 0 ? (int) round(100 * $zeile['geloest'] / $zeile['versuche']) : 0,
			'bestWertung' => (int) round((float) $zeile['bestWertung']),
			'bestDatum'   => (int) $zeile['bestDatum'] > 0 ? Date::parse(Config::get('dateFormat'), (int) $zeile['bestDatum']) : '',
			'eigene'      => null !== $eigenerName && $zeile['username'] === $eigenerName,
		);
	}
}
