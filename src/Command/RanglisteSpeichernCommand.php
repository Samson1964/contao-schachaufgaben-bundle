<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Command;

use Schachbulle\ContaoSchachaufgabenBundle\Training\Ranglistenstand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Konsolenbefehl schachaufgaben:rangliste-speichern.
 *
 * Macht von Hand, was der Cronjob tut: den Stand des laufenden Monats anlegen,
 * falls er noch fehlt. Rückwirkende Stände für frühere Monate gibt es bewusst
 * nicht, denn sie enthielten die heutigen Zahlen statt die vom Stichtag.
 */
#[AsCommand(name: 'schachaufgaben:rangliste-speichern', description: 'Speichert die Rangliste des laufenden Monats (Stichtag: Monatserster), falls sie noch fehlt.')]
class RanglisteSpeichernCommand extends Command
{
	/**
	 * Übernimmt den Dienst, der die Rangliste speichert.
	 *
	 * @param Ranglistenstand $ranglistenstand Speichert den Stand zum Stichtag
	 */
	public function __construct(private readonly Ranglistenstand $ranglistenstand)
	{
		parent::__construct();
	}

	/**
	 * Speichert den Stand des laufenden Monats und meldet das Ergebnis.
	 *
	 * @param InputInterface  $input  Wird nicht ausgewertet
	 * @param OutputInterface $output Ausgabe
	 *
	 * @return int Immer Command::SUCCESS; ein schon vorhandener Stand ist kein Fehler
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		$stichtag = Ranglistenstand::stichtag(time());

		if ($this->ranglistenstand->vorhanden($stichtag)) {
			$io->note(sprintf('Für den %s ist schon ein Stand gespeichert.', date('d.m.Y', $stichtag)));

			return Command::SUCCESS;
		}

		$anzahl = $this->ranglistenstand->speichern($stichtag);
		$io->success(sprintf('Stand zum %s gespeichert: %d Mitglieder.', date('d.m.Y', $stichtag), $anzahl));

		return Command::SUCCESS;
	}
}
