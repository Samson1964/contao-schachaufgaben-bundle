<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Setzt die von Lichess übernommene Statistik auf 0 (Update von 1.0.0).
 *
 * Fassung 1.0.0 hat Beliebtheit und Spielzahl von Lichess in die Felder
 * „beliebtheit" und „spiele" geschrieben. Seit 1.1.0 führen diese Felder die
 * eigene Statistik der Website; die Lichess-Werte dienen nur noch als Filter
 * beim Import. Ohne das Zurücksetzen stünden in der Liste Lichess-Zahlen
 * neben den eigenen, und die Beliebtheit passte nicht zu den Stimmen.
 *
 * Erkennungsmerkmal einer 1.0.0-Tabelle ist die fehlende Spalte „gefaellt".
 * Die Migration legt diese Spalte selbst an: Contao wiederholt Migrationen,
 * bis keine mehr aussteht, und gleicht das Schema erst danach ab. Überließe
 * man die Spalte dem Schemaabgleich, liefe die Migration bis zur
 * Schleifengrenze immer wieder (so im Test am 2026-09-27 geschehen).
 *
 * Die auf der eigenen Website unter 1.0.0 gespielten Versuche lassen sich von
 * den Lichess-Zahlen nicht trennen und gehen dabei mit verloren.
 */
class LichessStatistikMigration extends AbstractMigration
{
	/**
	 * Übernimmt die Datenbankverbindung.
	 *
	 * @param Connection $connection Die Datenbankverbindung von Contao
	 */
	public function __construct(private readonly Connection $connection)
	{
	}

	/**
	 * Beschreibt die Migration in der Ausgabe von contao:migrate.
	 *
	 * @return string Ein kurzer deutscher Text
	 */
	public function getName(): string
	{
		return 'Schachaufgaben: von Lichess übernommene Beliebtheit und Spielzahl zurücksetzen';
	}

	/**
	 * Prüft, ob die Tabelle noch im Zustand von 1.0.0 ist.
	 *
	 * @return bool true, wenn tl_schachaufgaben existiert, aber noch keine
	 *              Spalte „gefaellt" hat
	 */
	public function shouldRun(): bool
	{
		$schema = $this->connection->createSchemaManager();

		if (!$schema->tablesExist(array('tl_schachaufgaben'))) {
			return false;
		}

		return !\array_key_exists('gefaellt', array_change_key_case($schema->listTableColumns('tl_schachaufgaben'), CASE_LOWER));
	}

	/**
	 * Setzt Beliebtheit und Spielzahl der Lichess-Aufgaben auf 0 und legt die
	 * Spalte „gefaellt" an, damit die Migration kein zweites Mal läuft.
	 *
	 * Die Spaltendefinition entspricht der DCA; der anschließende Schemaabgleich
	 * findet sie deshalb schon vor und ändert nichts mehr daran.
	 *
	 * @return MigrationResult Erfolg samt Anzahl der geänderten Aufgaben
	 */
	public function run(): MigrationResult
	{
		$anzahl = $this->connection->executeStatement(
			"UPDATE tl_schachaufgaben SET beliebtheit=0, spiele=0 WHERE quelle='lichess'"
		);

		$this->connection->executeStatement("ALTER TABLE tl_schachaufgaben ADD gefaellt INT UNSIGNED DEFAULT 0 NOT NULL");

		return $this->createResult(true, sprintf('%d Aufgaben zurückgesetzt.', $anzahl));
	}
}
