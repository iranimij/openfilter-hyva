<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Unit\Observer;

use Hyva\Theme\Service\CurrentTheme;
use Iranimij\OpenFilter\Model\Config;
use Iranimij\OpenFilterHyva\Observer\AddLayerHandles;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Model\Layout\Merge;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AddLayerHandlesTest extends TestCase
{
    /**
     * @return array<string, array{string[], bool, bool, string[]}>
     */
    public static function pages(): array
    {
        $category = ['default', 'catalog_category_view', 'catalog_category_view_type_layered'];

        return [
            'layered category on Hyvä' => [$category, true, true, ['openfilter_hyva_layer', 'openfilter_hyva_category']],
            'search results on Hyvä' => [
                ['default', 'catalogsearch_result_index'],
                true,
                true,
                ['openfilter_hyva_layer', 'openfilter_hyva_search'],
            ],
            'OpenFilter off or stepping aside: stock templates' => [$category, true, false, []],
            'not a Hyvä theme' => [$category, false, true, []],
            'other pages' => [['default', 'cms_index_index'], true, true, []],
        ];
    }

    /**
     * @param string[] $handles
     * @param bool $hyva
     * @param bool $enabled
     * @param string[] $expected
     * @dataProvider pages
     */
    #[DataProvider('pages')]
    public function testAddsOpenFilterHandlesOnlyOnLayeredPagesOfHyvaThemesWhenEnabled(
        array $handles,
        bool $hyva,
        bool $enabled,
        array $expected
    ): void {
        $update = $this->createStub(Merge::class);
        $update->method('getHandles')->willReturn($handles);
        $added = [];
        $update->method('addHandle')->willReturnCallback(function ($handle) use (&$added, $update) {
            $added[] = $handle;
            return $update;
        });
        $layout = $this->createStub(LayoutInterface::class);
        $layout->method('getUpdate')->willReturn($update);
        $theme = $this->createStub(CurrentTheme::class);
        $theme->method('isHyva')->willReturn($hyva);
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);

        (new AddLayerHandles($theme, $config))->execute(new Observer(['layout' => $layout]));

        self::assertSame($expected, $added);
    }
}
