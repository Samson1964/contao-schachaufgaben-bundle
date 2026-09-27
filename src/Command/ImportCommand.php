<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Command;

use Schachbulle\ContaoSchachaufgabenBundle\Import\AufgabenPruefer;
use Schachbulle\ContaoSchachaufgabenBundle\Import\AufgabenSchreiber;
use Schachbulle\ContaoSchachaufgabenBundle\Import\ImportFilter;
use Schachbulle\ContaoSchachaufgabenBundle\Import\LichessCsvLeser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Konsolenbefehl schachaufgaben:import für die Lichess-Aufgabensammlung.
 *
 * Name und Beschreibung stehen im Attribut #[AsCommand], das Symfony 5.4
 * (Contao 4.13) wie Symfony 7 (Contao 5.7) auswertet; die Konsole erzeugt den
 * Befehl dadurch erst, wenn er aufgerufen wird.
 */
#[AsCommand(name: 'schachaufgaben:import', description: 'Importiert Aufgaben aus der Lichess-Aufgabensammlung (lichess_db_puzzle.csv).')]
class ImportCommand extends Command
{
	private LichessCsvLeser $leser;

	private AufgabenPruefer $pruefer;

	private AufgabenSchreiber $schreiber;

	/**
	 * Übernimmt die Bausteine des Imports aus dem Container.
	 *
	 * @param LichessCsvLeser   $leser     Liest die CSV zeilenweise
	 * @param AufgabenPruefer   $pruefer   Prüft FEN und Zugfolge
	 * @param AufgabenSchreiber $schreiber Schreibt blockweise in die Datenbank
	 */
	public function __construct(LichessCsvLeser $leser, AufgabenPruefer $pruefer, AufgabenSchreiber $schreiber)
	{
		$this->leser = $leser;
		$this->pruefer = $pruefer;
		$this->schreiber = $schreiber;

		parent::__construct();
	}

	/**
	 * Beschreibt Argument und Optionen des Befehls.
	 */
	protected function configure(): void
	{
		$this
			->addArgument('datei', InputArgument::REQUIRED, 'Pfad zur entpackten CSV-Datei, oder „-" für die Standardeingabe')
			->addOption('min-beliebtheit', null, InputOption::VALUE_REQUIRED, 'Nur Aufgaben mit mindestens dieser Beliebtheit bei Lichess (-100 bis 100; dient nur der Auswahl, gespeichert wird die eigene Statistik)', '-100')
			->addOption('min-spiele', null, InputOption::VALUE_REQUIRED, 'Nur Aufgaben, die bei Lichess mindestens so oft gespielt wurden (dient nur der Auswahl)', '0')
			->addOption('min-wertung', null, InputOption::VALUE_REQUIRED, 'Untergrenze der Wertungszahl', '0')
			->addOption('max-wertung', null, InputOption::VALUE_REQUIRED, 'Obergrenze der Wertungszahl', '9999')
			->addOption('motiv', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Nur Aufgaben mit diesem Motiv (mehrfach angebbar, eines genügt)')
			->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Höchstens so viele Aufgaben übernehmen (0 = alle)', '0')
			->addOption('aktualisieren', null, InputOption::VALUE_NONE, 'Vorhandene Aufgaben mit den Lichess-Daten überschreiben')
			->addOption('wertung-uebernehmen', null, InputOption::VALUE_NONE, 'Beim Aktualisieren auch die Wertung von Lichess übernehmen')
			->addOption('unveroeffentlicht', null, InputOption::VALUE_NONE, 'Neue Aufgaben nicht sofort veröffentlichen')
			->addOption('probelauf', null, InputOption::VALUE_NONE, 'Nur zählen, nichts in die Datenbank schreiben')
			->setHelp(<<<'HILFE'
				Die Sammlung gibt es unter https://database.lichess.org/#puzzles (Lizenz CC0).

				Die Datei ist mit Zstandard gepackt und kann direkt durchgereicht werden:

				  <info>zstd -dc lichess_db_puzzle.csv.zst | php vendor/bin/contao-console schachaufgaben:import -</info>

				Beliebte, oft gespielte Aufgaben zwischen 800 und 2400:

				  <info>php vendor/bin/contao-console schachaufgaben:import lichess_db_puzzle.csv --min-beliebtheit=80 --min-spiele=1000 --min-wertung=800 --max-wertung=2400</info>

				Die Lichess-Datei ist nach Kennung sortiert, und die Kennungen sind
				zufällig vergeben. Mit --limit erhält man deshalb eine zufällige
				Auswahl über alle Schwierigkeiten.
				HILFE
			);
	}

	/**
	 * Liest die Datei, prüft und filtert jede Aufgabe und schreibt sie weg.
	 *
	 * Fehlerhafte Zeilen brechen den Import nicht ab, sondern werden gezählt;
	 * mit -v werden die ersten zwanzig mit Zeilennummer ausgegeben. Ein
	 * Abbruch mittendrin ist unschädlich: Die geschriebenen Blöcke bleiben
	 * erhalten, ein neuer Lauf überspringt sie.
	 *
	 * @param InputInterface  $input  Argument und Optionen
	 * @param OutputInterface $output Ausgabe für Fortschritt und Bilanz
	 *
	 * @return int Command::SUCCESS, oder Command::FAILURE wenn die Datei
	 *             nicht lesbar ist oder die Pflichtspalten fehlen
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);

		$filter = new ImportFilter(
			(int) $input->getOption('min-beliebtheit'),
			(int) $input->getOption('min-spiele'),
			(int) $input->getOption('min-wertung'),
			(int) $input->getOption('max-wertung'),
			array_values((array) $input->getOption('motiv'))
		);

		$limit = max(0, (int) $input->getOption('limit'));
		$probelauf = (bool) $input->getOption('probelauf');

		$this->schreiber->einstellen(
			(bool) $input->getOption('aktualisieren'),
			(bool) $input->getOption('wertung-uebernehmen'),
			!$input->getOption('unveroeffentlicht')
		);

		$zaehler = array('gelesen' => 0, 'fehlerhaft' => 0, 'gefiltert' => 0, 'uebernommen' => 0);
		$gemeldet = 0;

		try {
			foreach ($this->leser->lesen((string) $input->getArgument('datei')) as $zeile => $aufgabe) {
				++$zaehler['gelesen'];

				$fehler = $aufgabe['_fehler'] ?? $this->pruefer->pruefeFen((string) $aufgabe['fen']) ?? $this->pruefer->pruefeZuege((string) $aufgabe['zuege']);

				if (null !== $fehler) {
					++$zaehler['fehlerhaft'];

					if ($io->isVerbose() && $gemeldet++ < 20) {
						$io->writeln(sprintf('<comment>Zeile %d übersprungen:</comment> %s', $zeile, $fehler));
					}

					continue;
				}

				if (!$filter->passt($aufgabe)) {
					++$zaehler['gefiltert'];
					continue;
				}

				if (!$probelauf) {
					$this->schreiber->hinzufuegen($aufgabe);
				}

				++$zaehler['uebernommen'];

				if (0 === $zaehler['uebernommen'] % 10000) {
					$io->writeln(sprintf('%d Aufgaben übernommen, %d Zeilen gelesen …', $zaehler['uebernommen'], $zaehler['gelesen']));
				}

				if ($limit > 0 && $zaehler['uebernommen'] >= $limit) {
					break;
				}
			}
		} catch (\RuntimeException $e) {
			$io->error($e->getMessage());

			return Command::FAILURE;
		}

		$geschrieben = $probelauf ? 0 : $this->schreiber->abschliessen();

		$io->table(
			array('Zeilen gelesen', 'fehlerhaft', 'ausgefiltert', 'übernommen', 'in der Datenbank geändert'),
			array(array($zaehler['gelesen'], $zaehler['fehlerhaft'], $zaehler['gefiltert'], $zaehler['uebernommen'], $probelauf ? '– (Probelauf)' : $geschrieben))
		);

		if ($zaehler['fehlerhaft'] > 0 && !$io->isVerbose()) {
			$io->note('Mit -v werden die ersten fehlerhaften Zeilen angezeigt.');
		}

		$io->success($probelauf ? 'Probelauf beendet, es wurde nichts geschrieben.' : 'Import beendet.');

		return Command::SUCCESS;
	}
}
