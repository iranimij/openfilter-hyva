<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Integration;

use Iranimij\OpenFilter\Test\Integration\Catalog\M1Catalog;

/**
 * Search result pages get the same regions and templates as category pages; every filter link keeps the query.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class SearchPageTest extends HyvaPageTestCase
{
    public function testRegionsAndFilterLinksThatKeepTheQuery(): void
    {
        $this->getRequest()->setMethod('GET')->setQueryValue(['q' => M1Catalog::TOKEN, M1Catalog::COLOR => 'red']);
        $this->dispatch('catalogsearch/result/index');
        $html = (string) $this->getResponse()->getBody();

        foreach (['of-filters', 'of-state', 'of-results'] as $region) {
            self::assertSame(1, substr_count($html, 'id="' . $region . '"'), $region);
        }
        self::assertMatchesRegularExpression('#id="of-results"[^>]*>.*id="product-list"#s', $html);
        preg_match_all('#<a class="of-(?:option|swatch)[^"]*"\s+href="([^"]+)"#', $this->region($html, 'of-filters'), $links);
        self::assertNotEmpty($links[1]);
        foreach ($links[1] as $href) {
            self::assertStringContainsString('q=' . M1Catalog::TOKEN, html_entity_decode($href), $href);
        }
        self::assertMatchesRegularExpression('#class="of-chip"[^>]*href="[^"]*q=' . M1Catalog::TOKEN . '#', $html);
    }

    /**
     * Regression (found by the 1.1 benchmark): the chips used to count the products before the product list, which
     * ran the search before Magento's toolbar set the sort order and the page: search pages listed every product
     * in relevance order.
     */
    public function testTheListKeepsMagentosPageAndSortOrder(): void
    {
        $this->getRequest()->setMethod('GET')->setQueryValue([
            'q' => M1Catalog::TOKEN, 'product_list_order' => 'price', 'product_list_dir' => 'asc', 'product_list_limit' => '12',
        ]);
        $this->dispatch('catalogsearch/result/index');
        $html = (string) $this->getResponse()->getBody();

        preg_match_all('#class="product-item-link"[^>]*>\s*([^<]+?)\s*<#', $html, $names);
        $expected = array_map(static fn (int $i): string => 'Ofmone simple ' . $i, range(0, 11));
        self::assertSame($expected, $names[1], 'twelve products, cheapest first');
        preg_match('#id="of-count" hidden data-of-count="(\d+)"#', $html, $count);
        preg_match_all('#<span class="toolbar-number">(\d+)</span>#', $html, $toolbar);
        self::assertSame(end($toolbar[1]), $count[1] ?? null, 'the count is every product found');
        self::assertGreaterThan(12, (int) ($count[1] ?? 0));
        self::assertGreaterThan(strrpos($html, 'product-item-link'), strpos($html, 'id="of-count"'), 'read after the list');
    }

    public function testThePageWithoutFiltersStillNamesTheFilterDialog(): void
    {
        $this->getRequest()->setMethod('GET')->setQueryValue(['q' => 'zzqxnothingmatches']);
        $this->dispatch('catalogsearch/result/index');
        $filters = $this->region((string) $this->getResponse()->getBody(), 'of-filters');

        self::assertStringNotContainsString('<details class="of-group', $filters, 'no filters on an empty result');
        self::assertMatchesRegularExpression('#<dialog id="of-drawer"[^>]*aria-labelledby="of-filters-title"#', (string) $this->getResponse()->getBody());
        self::assertMatchesRegularExpression('#<h2 id="of-filters-title" class="of-filters__title of-sr">#', $filters);
    }
}
