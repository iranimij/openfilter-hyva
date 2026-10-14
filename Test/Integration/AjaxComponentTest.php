<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Integration;

use Iranimij\OpenFilter\Model\Config;

/**
 * The AJAX component is one Alpine root and one inline script, printed only while AJAX is on; options carry the
 * hooks it uses (data-of-link, data-of-value) and #of-state the product count it announces.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class AjaxComponentTest extends HyvaPageTestCase
{
    public function testOneComponentAndOneScriptWhileAjaxIsOn(): void
    {
        $html = $this->page([]);

        self::assertSame(1, substr_count($html, 'x-data="openFilter"'));
        self::assertSame(1, substr_count($html, "Alpine.data('openFilter'"));
        self::assertMatchesRegularExpression('#<a class="of-option[^"]*"\s+href="[^"]+" data-of-link data-of-value="\d+"#', $html);
        self::assertMatchesRegularExpression('#id="of-state"[^>]*data-of-count="\d+"[^>]*data-of-announce="\d+(?: |&\#x20;)products?"#', $html);
    }

    public function testNoComponentWhileAjaxIsOff(): void
    {
        $this->config(Config::XML_PATH_AJAX, '0');

        $html = $this->page([]);

        self::assertStringNotContainsString('x-data="openFilter"', $html);
        self::assertStringContainsString('id="of-filters"', $html, 'the links still work as plain navigation');
    }
}
