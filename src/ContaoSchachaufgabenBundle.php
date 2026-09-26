<?php

declare(strict_types=1);

/*
 * Schachaufgaben-Training für Contao nach dem Vorbild von lichess.org/training
 * — lauffähig unter Contao 4.13 und Contao 5.7.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSchachaufgabenBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle-Klasse des Schachaufgaben-Bundles.
 *
 * Die Klasse bleibt leer, weil das Bundle weder eigene Compiler-Pässe noch
 * abweichende Verzeichnisse braucht. Symfony leitet Name, Pfad und den Namen
 * der DI-Extension (ContaoSchachaufgabenExtension) aus dem Klassennamen ab;
 * die Klasse muss aber vorhanden sein, damit das Contao-Manager-Plugin sie
 * beim Kernel anmelden kann.
 */
class ContaoSchachaufgabenBundle extends Bundle
{
}
