<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Integration;

use Iranimij\OpenFilter\Model\Config\OptionImages;
use Iranimij\OpenFilter\Api\Data\FilterConfigInterface;
use Iranimij\OpenFilter\Api\FilterConfigRepositoryInterface;
use Iranimij\OpenFilter\Test\Integration\Catalog\M1Catalog;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Option templates per display mode: swatches drawn as SVG fills from core swatch data, "Show more" as a native
 * disclosure holding the options beyond the visible count, a search box above the threshold.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class OptionTemplatesTest extends HyvaPageTestCase
{
    private ?FilterConfigRepositoryInterface $repository = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Bootstrap::getObjectManager()->get(FilterConfigRepositoryInterface::class);
    }

    protected function tearDown(): void
    {
        $this->repository->resetToDefault(M1Catalog::COLOR);
        $db = Bootstrap::getObjectManager()->get(ResourceConnection::class)->getConnection();
        $db->delete($db->getTableName('eav_attribute_option_swatch'), [
            'option_id IN (?)' => array_values(self::$catalog['options'][M1Catalog::COLOR]),
        ]);
        $this->swatchInput(null);
        parent::tearDown();
    }

    public function testSwatchesDrawHexColoursAsSvgFillsWithVisibleLabels(): void
    {
        $this->swatchInput('visual');
        $this->colour(FilterConfigInterface::DISPLAY_SWATCH);
        $red = self::$catalog['options'][M1Catalog::COLOR]['Red'];
        $db = Bootstrap::getObjectManager()->get(ResourceConnection::class)->getConnection();
        $db->insert($db->getTableName('eav_attribute_option_swatch'), [
            'option_id' => $red, 'store_id' => 0, 'type' => 1, 'value' => '#ff0000',
        ]);

        $group = $this->group($this->page([]), M1Catalog::COLOR);

        self::assertMatchesRegularExpression(
            '#<a class="of-swatch" href="[^"]*' . M1Catalog::COLOR . '=red"[^>]*><svg[^>]*><rect width="1" height="1" '
            . 'fill="(?:\#|&\#x23;)ff0000"/></svg><span class="of-swatch__label">Red</span>#',
            $group
        );
        self::assertStringContainsString('<span class="of-swatch__visual of-swatch__text" aria-hidden="true">Blue</span>', $group);
        self::assertDoesNotMatchRegularExpression('#\sstyle=#', $group);
    }

    public function testTheImagesDisplayShowsTheMerchantsOptionImages(): void
    {
        $images = Bootstrap::getObjectManager()->get(OptionImages::class);
        $red = self::$catalog['options'][M1Catalog::COLOR]['Red'];
        $images->save([$red => 'r/e/red.png']);
        $this->colour(FilterConfigInterface::DISPLAY_IMAGES);
        try {
            $group = $this->group($this->page([]), M1Catalog::COLOR);
        } finally {
            $images->save([$red => null]);
        }

        self::assertMatchesRegularExpression(
            '#<a class="of-swatch" href="[^"]*' . M1Catalog::COLOR . '=red"[^>]*><img class="of-swatch__visual" '
            . 'src="[^"]*/openfilter/options/r/e/red\.png" alt="" width="36" height="36" loading="lazy">'
            . '<span class="of-swatch__label">Red</span>#',
            $group
        );
        self::assertStringContainsString('<span class="of-swatch__visual of-swatch__text" aria-hidden="true">Blue</span>', $group);
    }

    public function testShowMoreHoldsTheOptionsBeyondTheVisibleCount(): void
    {
        $this->colour(FilterConfigInterface::DISPLAY_LIST, 2);

        $group = $this->group($this->page([]), M1Catalog::COLOR);

        [$leading, $more] = explode('<details class="of-more">', $group) + [1 => ''];
        self::assertSame(2, substr_count($leading, '<a class="of-option'));
        self::assertStringContainsString('Show more (2)', $more);
        self::assertSame(2, substr_count($more, '<a class="of-option'));
    }

    public function testLongListsGetASearchBox(): void
    {
        $this->colour(FilterConfigInterface::DISPLAY_LIST, 6, 3);

        $group = $this->group($this->page([]), M1Catalog::COLOR);

        self::assertMatchesRegularExpression('#<input type="search" class="of-search__input" data-of-search#', $group);
    }

    public function testPriceSliderIsAGetFormThatKeepsTheOtherFilters(): void
    {
        $group = $this->group($this->page([M1Catalog::COLOR => 'red', 'price' => '20-40']), 'price');

        self::assertMatchesRegularExpression('#<form class="of-slider of-slider--js" method="get" action="[^"?]+"#', $group);
        self::assertMatchesRegularExpression('#<input type="hidden" name="' . M1Catalog::COLOR . '"\s+value="red">#', $group);
        self::assertMatchesRegularExpression('#name="price_from"[^>]*value="20"#', $group);
        self::assertMatchesRegularExpression('#name="price_to"[^>]*value="40"#', $group);
        self::assertSame(2, substr_count($group, 'type="range"'));
        self::assertMatchesRegularExpression('#data-of-url="[^"]*price=999999901-999999902#', $group);
    }

    public function testWithoutTheAjaxComponentTheSliderKeepsOnlyTheFormAndApply(): void
    {
        $this->config(\Iranimij\OpenFilter\Model\Config::XML_PATH_AJAX, '0');

        $group = $this->group($this->page([]), 'price');

        self::assertStringContainsString('<form class="of-slider" method="get"', $group);
        self::assertStringNotContainsString('type="range"', $group);
        self::assertStringContainsString('class="of-slider__apply"', $group);
    }

    /**
     * Makes the test colour a visual swatch attribute (or a plain select again with null).
     */
    private function swatchInput(?string $type): void
    {
        $om = Bootstrap::getObjectManager();
        $db = $om->get(ResourceConnection::class)->getConnection();
        $attributeId = (int) $db->fetchOne(
            $db->select()->from($db->getTableName('eav_attribute'), 'attribute_id')
                ->where('attribute_code = ?', M1Catalog::COLOR)->where('entity_type_id = ?', 4)
        );
        $db->update(
            $db->getTableName('catalog_eav_attribute'),
            ['additional_data' => $type === null ? null : json_encode(['swatch_input_type' => $type])],
            ['attribute_id = ?' => $attributeId]
        );
        $om->get(\Magento\Eav\Model\Config::class)->clear();
        $om->get(\Iranimij\OpenFilter\Model\Cache\Type::class)->clean();
    }

    private function colour(string $display, int $visible = 6, int $threshold = 12): void
    {
        $this->repository->save(
            $this->repository->getByCode(M1Catalog::COLOR)->setDisplayMode($display)->setVisibleOptions($visible)
                ->setSearchThreshold($threshold)
        );
    }
}
