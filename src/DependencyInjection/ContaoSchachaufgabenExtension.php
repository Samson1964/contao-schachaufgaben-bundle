<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Lädt die Dienstdefinitionen des Bundles in den Container.
 *
 * Ohne diese Klasse würde Symfony die services.yaml nie lesen, und sämtliche
 * Dienste, Hooks, DCA-Callbacks und Konsolenbefehle blieben stumm. Die
 * Basisklasse liegt in Symfony 5.4 (Contao 4.13) wie in Symfony 7 (Contao 5.7)
 * unter demselben Namensraum, die Datei läuft also unverändert unter beiden.
 */
class ContaoSchachaufgabenExtension extends Extension
{
	/**
	 * Liest die services.yaml des Bundles ein.
	 *
	 * @param array<int|string, mixed> $mergedConfig Die zusammengeführte
	 *                                               Bundle-Konfiguration; das
	 *                                               Bundle wertet sie bisher
	 *                                               nicht aus
	 * @param ContainerBuilder         $container    Der Container, in den die
	 *                                               Definitionen geschrieben werden
	 */
	public function load(array $mergedConfig, ContainerBuilder $container): void
	{
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__.'/../Resources/config')
		);

		$loader->load('services.yaml');
	}
}
