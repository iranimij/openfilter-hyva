<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Unit\ViewModel;

use Iranimij\OpenFilter\Model\Config;
use Iranimij\OpenFilterHyva\ViewModel\Ajax;
use PHPUnit\Framework\TestCase;

class AjaxTest extends TestCase
{
    public function testFollowsOpenFilterAndItsAjaxSetting(): void
    {
        foreach ([[true, true, true], [true, false, false], [false, true, false]] as [$enabled, $ajax, $expected]) {
            $config = $this->createStub(Config::class);
            $config->method('isEnabled')->willReturn($enabled);
            $config->method('isAjaxEnabled')->willReturn($ajax);

            self::assertSame($expected, (new Ajax($config))->isEnabled());
        }
    }
}
