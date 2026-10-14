<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Integration;

use Iranimij\OpenFilter\Model\Config;
use Iranimij\OpenFilter\Test\Integration\Catalog\M1Catalog;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * A layered category page on Hyvä with OpenFilter's templates: the three regions, a real link for every option
 * (also in closed groups), no inline styles or handlers; switched off, the page keeps Hyvä's stock templates.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class CategoryPageTest extends HyvaPageTestCase
{
    public function testRegionsWrapFiltersStateAndResults(): void
    {
        $html = $this->page([M1Catalog::COLOR => 'red']);

        foreach (['of-filters', 'of-state', 'of-results'] as $region) {
            self::assertSame(1, substr_count($html, 'id="' . $region . '"'), $region);
        }
        self::assertLessThan(strpos($html, 'id="of-results"'), strpos($html, 'id="of-state"'), 'chips above the results');
        self::assertMatchesRegularExpression('#id="of-results"[^>]*>.*id="product-list"#s', $html);
        self::assertStringContainsString('css/openfilter.css', $html);
        self::assertStringNotContainsString('x-defer="intersect"', $this->region($html, 'of-filters'));
    }

    public function testEveryOptionIsARealLinkAlsoInClosedGroups(): void
    {
        $this->config(Config::XML_PATH_EXPANDED_GROUPS, '1');
        $filters = $this->region($this->page([]), 'of-filters');

        self::assertSame(5, substr_count($filters, '<details class="of-group'), 'colour, size, material, price, width');
        foreach (array_keys(M1Catalog::OPTIONS) as $code) {
            self::assertMatchesRegularExpression(
                '#data-of-group="' . $code . '".*?<a class="of-option[^"]*"\s+href="[^"]*[?&]' . $code . '=#s',
                $filters,
                "$code options link to filtered URLs"
            );
        }
        preg_match_all('#<details class="of-group[^>]*>#', $filters, $groups);
        self::assertSame(
            [true, false, false, false, false],
            array_map(static fn ($tag) => str_contains($tag, ' open'), $groups[0]),
            'first group open, the others closed but still carrying their links'
        );
    }

    public function testSelectedOptionsAreMarkedAndChipsRemoveThem(): void
    {
        $html = $this->page([M1Catalog::COLOR => 'red']);

        self::assertMatchesRegularExpression('#<a class="of-option of-option--selected"[^>]*aria-current="true"#s', $html);
        self::assertMatchesRegularExpression('#id="of-state".*class="of-chip"#s', $html);
        self::assertMatchesRegularExpression('#class="of-clear" href="[^"?]*"#', $html, 'Clear All drops every filter');
    }

    public function testOptionLinksStayFollowableBelowTheNofollowThreshold(): void
    {
        $this->config(Config::XML_PATH_NOFOLLOW_THRESHOLD, '2');
        $filters = $this->region($this->page([M1Catalog::COLOR => 'red']), 'of-filters');

        self::assertStringNotContainsString('rel="nofollow"', $filters, 'one active value: links stay followable');
    }

    public function testOptionLinksAskCrawlersNotToFollowFromTheThreshold(): void
    {
        $this->config(Config::XML_PATH_NOFOLLOW_THRESHOLD, '2');
        $filters = $this->region($this->page([M1Catalog::COLOR => 'red,blue']), 'of-filters');

        self::assertMatchesRegularExpression('#<a class="of-option"[^>]*rel="nofollow"#s', $filters, 'adding a third value');
        self::assertDoesNotMatchRegularExpression(
            '#<a class="of-option of-option--selected"[^>]*rel="nofollow"#s',
            $filters,
            'links that remove a value stay followable'
        );
    }

    public function testNoDebugAttributesOutsideDebugMode(): void
    {
        self::assertStringNotContainsString('data-of-debug-', $this->page([M1Catalog::COLOR => 'red']));
    }

    public function testDebugModeShowsTheServerDecisionsOnTheMarkup(): void
    {
        Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class)
            ->setValue(Config::XML_PATH_DEBUG, '1', 'default'); // a global setting
        $filters = $this->region($this->page([M1Catalog::COLOR => 'red']), 'of-filters');

        self::assertMatchesRegularExpression(
            '#id="of-filters"[^>]*data-of-debug-strategy="(single_query|per_attribute)"[^>]*data-of-debug-values="1"#',
            $filters
        );
        self::assertMatchesRegularExpression(
            '#data-of-group="' . M1Catalog::COLOR . '"[^>]*data-of-debug-filter="' . M1Catalog::COLOR . '"#s',
            $filters
        );
        self::assertMatchesRegularExpression('#<a class="of-option[^>]*data-of-debug-option="\d+" data-of-debug-count="\d+"#s', $filters);
    }

    public function testRegionsCarryNoInlineStylesOrHandlers(): void
    {
        $html = $this->page([M1Catalog::COLOR => 'red']);

        foreach (['of-filters', 'of-state'] as $region) {
            $markup = $this->region($html, $region);
            self::assertDoesNotMatchRegularExpression('#\sstyle=#i', $markup, "$region: no inline styles");
            self::assertDoesNotMatchRegularExpression('#\son[a-z]+=#i', $markup, "$region: no inline handlers");
            self::assertStringNotContainsString('javascript:', $markup);
        }
    }

    public function testSwitchedOffThePageKeepsHyvasStockTemplates(): void
    {
        $this->config(Config::XML_PATH_ENABLED, '0');

        $html = $this->page([]);

        self::assertStringNotContainsString('id="of-filters"', $html);
        self::assertStringNotContainsString('openfilter.css', $html);
        self::assertStringContainsString('filter-option', $html, "Hyvä's own filter template");
        self::assertStringContainsString('frontend/Hyva/default/', $html, 'rendered with Hyvä');
    }
}
