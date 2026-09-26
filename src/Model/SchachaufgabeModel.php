<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Model;

use Contao\Model;

/**
 * Model für eine Zeile der Tabelle tl_schachaufgaben.
 *
 * @property int         $id
 * @property int         $tstamp
 * @property string      $fen                 Stellung vor dem auslösenden Gegnerzug
 * @property string      $zuege               UCI-Züge, durch Leerzeichen getrennt
 * @property string      $motive              Motive im Lichess-Format, durch Leerzeichen getrennt
 * @property int         $wertung             Glicko-2-Wertungszahl
 * @property int         $wertungAbweichung   Glicko-2-Abweichung (RD)
 * @property float       $wertungVolatilitaet Glicko-2-Volatilität
 * @property int         $beliebtheit         -100 bis 100
 * @property int         $spiele              Anzahl der Lösungsversuche
 * @property string      $quelle              manuell, lichess oder pgn
 * @property string|null $lichessId
 * @property string      $partieUrl
 * @property string      $eroeffnung
 * @property string      $published
 */
class SchachaufgabeModel extends Model
{
	/**
	 * Name der Datenbanktabelle.
	 *
	 * @var string
	 */
	protected static $strTable = 'tl_schachaufgaben';

	/**
	 * Sucht eine veröffentlichte Aufgabe über ihre Lichess-Kennung.
	 *
	 * Die Spalte hat eine binäre Sortierfolge, der Vergleich unterscheidet
	 * also Groß- und Kleinschreibung — so wie Lichess selbst.
	 *
	 * @param string $lichessId Die Kennung aus der Lichess-CSV, z. B. „00sHx"
	 *
	 * @return static|null Die Aufgabe, oder null wenn sie nicht existiert
	 *                     oder nicht veröffentlicht ist
	 */
	public static function findPublishedByLichessId(string $lichessId): ?self
	{
		$t = static::$strTable;

		return static::findOneBy(array("$t.lichessId=?", "$t.published='1'"), array($lichessId));
	}

	/**
	 * Zerlegt die gespeicherte Zugfolge in einzelne UCI-Züge.
	 *
	 * Der erste Eintrag ist der Gegnerzug, der die Aufgabe auslöst; die
	 * Lösung beginnt mit dem zweiten Eintrag.
	 *
	 * @return array<int, string> Die Züge in Spielreihenfolge; ein leeres
	 *                            Array, wenn keine Züge hinterlegt sind
	 */
	public function getZugliste(): array
	{
		return preg_split('/\s+/', trim((string) $this->zuege), -1, PREG_SPLIT_NO_EMPTY) ?: array();
	}
}
