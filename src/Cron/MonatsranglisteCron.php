<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Schachbulle\ContaoSchachaufgabenBundle\Training\Ranglistenstand;

/**
 * Speichert am Monatsersten die Rangliste.
 *
 * Der Cronjob läuft stündlich statt monatlich: Contaos Cron wird nur
 * ausgeführt, wenn jemand die Website aufruft oder contao:cron läuft. Würde
 * er nur einmal im Monat angestoßen und genau diese Gelegenheit verpassen,
 * fehlte der Stand. So wird er beim ersten Lauf nach Monatsbeginn angelegt;
 * alle weiteren Läufe im Monat finden ihn vor und kosten nur eine Abfrage.
 */
#[AsCronJob('hourly')]
class MonatsranglisteCron
{
	/**
	 * Übernimmt den Dienst, der die Rangliste speichert.
	 *
	 * @param Ranglistenstand $ranglistenstand Speichert den Stand zum Stichtag
	 */
	public function __construct(private readonly Ranglistenstand $ranglistenstand)
	{
	}

	/**
	 * Legt den Stand des laufenden Monats an, falls er noch fehlt.
	 */
	public function __invoke(): void
	{
		$this->ranglistenstand->speichern(Ranglistenstand::stichtag(time()));
	}
}
