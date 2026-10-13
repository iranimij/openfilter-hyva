<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Unit;

use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;

/**
 * Guards the module identity and its dependencies: the shared core iranimij/module-base, OpenFilter core,
 * Magento core and Hyvä (suggested only).
 */
class ModuleTest extends TestCase
{
    private const MODULE_NAME = 'Iranimij_OpenFilterHyva';

    public function testModuleIsRegisteredWithComponentRegistrar(): void
    {
        $path = (new ComponentRegistrar())->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);

        self::assertNotNull($path, 'registration.php must register ' . self::MODULE_NAME);
        self::assertSame(realpath(dirname(__DIR__, 2)), realpath((string) $path));
    }

    public function testModuleLoadsAfterBaseOpenFilterAndHyvaTheme(): void
    {
        $xml = simplexml_load_file(dirname(__DIR__, 2) . '/etc/module.xml');

        self::assertNotFalse($xml);
        self::assertSame(self::MODULE_NAME, (string) $xml->module['name']);
        $sequence = array_map(
            static fn ($m) => (string) $m['name'],
            iterator_to_array($xml->module->sequence->module, false)
        );
        self::assertContains('Iranimij_Base', $sequence);
        self::assertContains('Iranimij_OpenFilter', $sequence);
        self::assertContains('Hyva_Theme', $sequence);
    }

    public function testComposerRequiresBaseCoreAndHyvasThemeModuleAndSuggestsTheTheme(): void
    {
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true);

        self::assertSame('MIT', $composer['license']);
        self::assertArrayHasKey('iranimij/module-base', $composer['require']);
        self::assertArrayHasKey('iranimij/openfilter', $composer['require']);
        self::assertArrayHasKey('hyva-themes/magento2-theme-module', $composer['require']);
        self::assertArrayHasKey('hyva-themes/magento2-default-theme', $composer['suggest']);
        foreach (array_keys($composer['require']) as $package) {
            self::assertMatchesRegularExpression(
                '#^(php|ext-[a-z0-9_]+|magento/[a-z0-9-]+|iranimij/(module-base|openfilter)|hyva-themes/magento2-theme-module)$#',
                $package,
                'Only php, extensions, magento/*, iranimij/module-base, iranimij/openfilter and Hyvä\'s theme module'
            );
        }
    }

    public function testOpenFilterLayoutHandlesExist(): void
    {
        foreach (['openfilter_hyva_layer', 'openfilter_hyva_category'] as $handle) {
            self::assertFileExists(dirname(__DIR__, 2) . '/view/frontend/layout/' . $handle . '.xml');
        }
    }
}
