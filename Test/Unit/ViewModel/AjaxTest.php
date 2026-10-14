<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Unit\ViewModel;

use Iranimij\OpenFilter\Model\Config;
use Iranimij\OpenFilter\Model\Config\Defaults;
use Iranimij\OpenFilter\Model\Config\FilterConfigProvider;
use Iranimij\OpenFilterHyva\ViewModel\Ajax;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AjaxTest extends TestCase
{
    public function testFollowsOpenFilterAndItsAjaxSetting(): void
    {
        foreach ([[true, true, true], [true, false, false], [false, true, false]] as [$enabled, $ajax, $expected]) {
            self::assertSame($expected, $this->ajax($enabled, $ajax)->isEnabled());
        }
    }

    /**
     * @return array<string, array{bool, string, string, string, bool}>
     */
    public static function extras(): array
    {
        return [
            '1.0 defaults: no extras' => [true, 'instant', 'sidebar', 'list', false],
            'desktop apply mode' => [true, 'button', 'sidebar', 'list', true],
            'horizontal bar' => [true, 'instant', 'horizontal', 'list', true],
            'a dropdown filter' => [true, 'instant', 'sidebar', 'dropdown', true],
            'AJAX off: nothing' => [false, 'button', 'horizontal', 'dropdown', false],
        ];
    }

    /**
     * @dataProvider extras
     */
    #[DataProvider('extras')]
    public function testTheExtrasScriptOnlyWhereAModeNeedsIt(bool $ajax, string $apply, string $layout, string $display, bool $expected): void
    {
        self::assertSame($expected, $this->ajax(true, $ajax, $apply, $layout, $display)->needsExtras());
    }

    private function ajax(bool $enabled, bool $ajax, string $apply = 'instant', string $layout = 'sidebar', string $display = 'list'): Ajax
    {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $config->method('isAjaxEnabled')->willReturn($ajax);
        $config->method('getApplyMode')->willReturn($apply);
        $config->method('getLayout')->willReturn($layout);
        $filters = $this->createStub(FilterConfigProvider::class);
        $filters->method('getActive')->willReturn([
            'climate' => (new Defaults())->forAttribute('climate', 90, 'multiselect', null, 10)->setDisplayMode($display),
        ]);
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $stores = $this->createStub(StoreManagerInterface::class);
        $stores->method('getStore')->willReturn($store);

        return new Ajax($config, $filters, $stores);
    }
}
