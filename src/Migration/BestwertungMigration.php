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
use Schachbulle\ContaoSchachaufgabenBundle\Training\Training;

/**
 * Füllt Bestwertung und erste Nutzung für Mitglieder, die schon vor 1.2.0
 * gespielt haben.
 *
 * Die Spalten bestWertung, bestDatum und ersteNutzung kommen mit 1.2.0. Für
 * bestehende Mitglieder lässt sich die Vergangenheit nur annähern:
 *
 * - erste Nutzung: der früheste Eintrag in tl_schachaufgaben_versuch;
 * - Bestwertung: die aktuelle Wertung, falls sie gesichert ist, mit dem
 *   Zeitpunkt der letzten Änderung. Frühere Höchststände sind nicht
 *   gespeichert und lassen sich nicht zurückgewinnen.
 *
 * Wie LichessStatistikMigration legt die Migration die Spalten selbst an:
 * Contao wiederholt Migrationen, bis keine mehr aussteht, und gleicht das
 * Schema erst danach ab.
 */
class BestwertungMigration extends AbstractMigration
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
		return 'Schachaufgaben: Bestwertung und erste Nutzung der Mitglieder nachtragen';
	}

	/**
	 * Prüft, ob tl_schachaufgaben_spieler noch ohne Spalte bestWertung ist.
	 *
	 * @return bool true, wenn die Tabelle existiert, die Spalte aber fehlt
	 */
	public function shouldRun(): bool
	{
		$schema = $this->connection->createSchemaManager();

		if (!$schema->tablesExist(array('tl_schachaufgaben_spieler'))) {
			return false;
		}

		return !\array_key_exists('bestwertung', array_change_key_case($schema->listTableColumns('tl_schachaufgaben_spieler'), CASE_LOWER));
	}

	/**
	 * Legt die Spalten an und füllt sie aus den vorhandenen Daten.
	 *
	 * Die Spaltendefinitionen entsprechen der DCA, der Schemaabgleich danach
	 * findet sie deshalb unverändert vor.
	 *
	 * @return MigrationResult Erfolg samt Anzahl der bearbeiteten Mitglieder
	 */
	public function run(): MigrationResult
	{
		$this->connection->executeStatement(
			"ALTER TABLE tl_schachaufgaben_spieler
			 ADD bestWertung DOUBLE PRECISION DEFAULT '0' NOT NULL,
			 ADD bestDatum INT UNSIGNED DEFAULT 0 NOT NULL,
			 ADD ersteNutzung INT UNSIGNED DEFAULT 0 NOT NULL"
		);

		$anzahl = $this->connection->executeStatement(
			'UPDATE tl_schachaufgaben_spieler s
			 SET s.ersteNutzung = COALESCE((SELECT MIN(v.tstamp) FROM tl_schachaufgaben_versuch v WHERE v.memberId = s.memberId), s.tstamp)'
		);

		$this->connection->executeStatement(
			'UPDATE tl_schachaufgaben_spieler SET bestWertung = wertung, bestDatum = tstamp WHERE wertungAbweichung <= ?',
			array(Training::GESICHERTE_ABWEICHUNG)
		);

		return $this->createResult(true, sprintf('%d Mitglieder ergänzt.', $anzahl));
	}
}
