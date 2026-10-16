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
     * @return array<string, array{0: bool, 1: string, 2: string, 3: string, 4: bool, 5?: string}>
     */
    public static function extras(): array
    {
        return [
            '1.0 defaults: no extras' => [true, 'instant', 'sidebar', 'list', false],
            'desktop apply mode' => [true, 'button', 'sidebar', 'list', true],
            'horizontal bar' => [true, 'instant', 'horizontal', 'list', true],
            'a dropdown filter' => [true, 'instant', 'sidebar', 'dropdown', true],
            'AJAX off: nothing' => [false, 'button', 'horizontal', 'dropdown', false],
            'an H1 template' => [true, 'instant', 'sidebar', 'list', true, '{filters} {category}'],
            'AJAX off with an H1 template: nothing' => [false, 'instant', 'sidebar', 'list', false, '{filters} {category}'],
        ];
    }

    /**
     * @dataProvider extras
     */
    #[DataProvider('extras')]
    public function testTheExtrasScriptOnlyWhereAModeNeedsIt(
        bool $ajax,
        string $apply,
        string $layout,
        string $display,
        bool $expected,
        string $h1 = ''
    ): void {
        self::assertSame($expected, $this->ajax(true, $ajax, $apply, $layout, $display, $h1)->needsExtras());
    }

    public function testMoreProductsFollowTheSettingAndNeedTheAjaxComponent(): void
    {
        self::assertSame('pages', $this->ajax(true, true)->pagination(), '1.1 behaviour by default');
        self::assertSame('infinite', $this->ajax(true, true, pagination: 'infinite')->pagination());
        self::assertSame('pages', $this->ajax(true, false, pagination: 'load_more')->pagination(), 'no AJAX: the pager');
    }

    private function ajax(
        bool $enabled,
        bool $ajax,
        string $apply = 'instant',
        string $layout = 'sidebar',
        string $display = 'list',
        string $h1 = '',
        string $pagination = 'pages'
    ): Ajax {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $config->method('isAjaxEnabled')->willReturn($ajax);
        $config->method('getApplyMode')->willReturn($apply);
        $config->method('getLayout')->willReturn($layout);
        $config->method('getH1Template')->willReturn($h1);
        $config->method('getPagination')->willReturn($pagination);
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
