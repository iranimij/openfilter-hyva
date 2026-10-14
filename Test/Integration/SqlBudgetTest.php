<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Integration;

use Iranimij\OpenFilter\Model\Config;
use Iranimij\OpenFilter\Test\Integration\Catalog\M1Catalog;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * "0 extra MySQL queries" (07 §6) with the Hyvä templates rendering (M3 deferred: the core BudgetTest renders
 * the integration sandbox's default theme): a Hyvä category page and search page with OpenFilter's templates run no
 * more SQL queries than the same pages with Hyvä's stock layered navigation (OpenFilter off). Caches warmed first;
 * one dispatch per test.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 * @magentoCache openfilter enabled
 * @magentoCache config enabled
 * @magentoCache eav enabled
 */
class SqlBudgetTest extends HyvaPageTestCase
{
    /** @var array<string, int> SQL queries by run */
    private static ?array $queries = [];

    public function testWarmUpCategory(): void
    {
        self::$queries['warm-category'] = $this->category(true);
    }

    public function testWarmUpCoreCategory(): void
    {
        self::$queries['warm-core-category'] = $this->category(false);
    }

    public function testWarmUpSearch(): void
    {
        self::$queries['warm-search'] = $this->search(true);
    }

    public function testWarmUpCoreSearch(): void
    {
        self::$queries['warm-core-search'] = $this->search(false);
    }

    public function testOpenFilterCategory(): void
    {
        self::$queries['category'] = $this->category(true);
    }

    public function testCoreCategory(): void
    {
        self::$queries['core-category'] = $this->category(false);
    }

    public function testOpenFilterSearch(): void
    {
        self::$queries['search'] = $this->search(true);
    }

    public function testCoreSearch(): void
    {
        self::$queries['core-search'] = $this->search(false);
    }

    /**
     * @depends testOpenFilterCategory
     * @depends testCoreCategory
     * @depends testOpenFilterSearch
     * @depends testCoreSearch
     */
    public function testNoExtraQueriesWithTheHyvaTemplates(): void
    {
        fwrite(STDERR, sprintf(
            "\n[budget, Hyvä templates] SQL per page: category OpenFilter %d / stock %d, search OpenFilter %d / stock %d\n",
            self::$queries['category'],
            self::$queries['core-category'],
            self::$queries['search'],
            self::$queries['core-search']
        ));

        self::assertLessThanOrEqual(self::$queries['core-category'], self::$queries['category'], 'category page');
        self::assertLessThanOrEqual(self::$queries['core-search'], self::$queries['search'], 'search page');
    }

    private function category(bool $openFilter): int
    {
        $options = self::$catalog['options'];
        $query = $openFilter
            ? [M1Catalog::COLOR => 'red', M1Catalog::SIZE => 'm'] // the same products on both sides (core: one value)
            : [M1Catalog::COLOR => (string) $options[M1Catalog::COLOR]['Red'], M1Catalog::SIZE => (string) $options[M1Catalog::SIZE]['M']];

        return $this->queriesFor($openFilter, $query, 'catalog/category/view/id/' . self::$catalog['category']);
    }

    private function search(bool $openFilter): int
    {
        $red = $openFilter ? 'red' : (string) self::$catalog['options'][M1Catalog::COLOR]['Red'];

        return $this->queriesFor($openFilter, ['q' => M1Catalog::TOKEN, M1Catalog::COLOR => $red], 'catalogsearch/result/index');
    }

    /**
     * @param array<string, string> $query
     */
    private function queriesFor(bool $openFilter, array $query, string $uri): int
    {
        if (!$openFilter) {
            $this->config(Config::XML_PATH_ENABLED, '0');
        }
        $profiler = Bootstrap::getObjectManager()->get(ResourceConnection::class)->getConnection()->getProfiler();
        $profiler->clear();
        $profiler->setEnabled(true);
        $this->getRequest()->setMethod('GET')->setQueryValue($query);
        $this->dispatch($uri);
        $queries = (int) $profiler->getTotalNumQueries();
        $profiler->setEnabled(false);
        self::assertSame(200, $this->getResponse()->getHttpResponseCode());
        $html = (string) $this->getResponse()->getBody();
        self::assertSame($openFilter, str_contains($html, 'id="of-filters"'), 'the side under test renders');

        return $queries;
    }
}
