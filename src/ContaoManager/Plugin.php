<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Schachbulle\ContaoSchachaufgabenBundle\ContaoSchachaufgabenBundle;

/**
 * Meldet das Bundle beim Contao Manager an.
 *
 * Ohne diese Klasse taucht das Bundle nicht im Kernel auf, weil Contao die
 * Bundle-Liste aus den Plugins aller installierten Pakete zusammensetzt.
 */
class Plugin implements BundlePluginInterface
{
	/**
	 * Meldet das Bundle beim Kernel an.
	 *
	 * Das Bundle wird nach dem Contao-Core geladen, damit dessen DCA-Dateien
	 * (etwa tl_member und tl_module) bereits vorliegen, wenn das Bundle eigene
	 * Felder und Paletten ergänzt.
	 *
	 * @param ParserInterface $parser Wird nicht ausgewertet, weil das Bundle
	 *                                keine Konfigurationsdateien parsen lässt
	 *
	 * @return array<int, BundleConfig> Die Konfiguration dieses einen Bundles
	 */
	public function getBundles(ParserInterface $parser): array
	{
		return array(
			BundleConfig::create(ContaoSchachaufgabenBundle::class)
				->setLoadAfter(array(ContaoCoreBundle::class)),
		);
	}
}
