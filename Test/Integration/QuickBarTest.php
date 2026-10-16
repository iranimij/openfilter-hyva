<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Integration;

use Iranimij\OpenFilter\Api\FilterConfigRepositoryInterface;
use Iranimij\OpenFilter\Test\Integration\Catalog\M1Catalog;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * M6 S3 quick filter bar: off by default; with a size and a marked filter, links to its values in #of-state (so AJAX
 * swaps refresh it), picked value first and marked as current.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class QuickBarTest extends HyvaPageTestCase
{
    protected function tearDown(): void
    {
        $this->mark(false);
        parent::tearDown();
    }

    public function testNoBarByDefault(): void
    {
        $this->mark(true);

        self::assertStringNotContainsString('of-quick', $this->region($this->page([]), 'of-state'));
    }

    public function testTheMarkedFiltersValuesWithThePickFirst(): void
    {
        $this->mark(true);
        $this->config('openfilter/display/quick_bar_size', '3');

        $state = $this->region($this->page([M1Catalog::COLOR => 'blue']), 'of-state');

        self::assertSame(1, preg_match('#<nav class="of-quick" aria-label="Quick(?: |&\#x20;)filters">(.*?)</nav>#s', $state, $nav));
        self::assertSame(3, preg_match_all('#<a class="of-quick__link#', $nav[1]));
        self::assertMatchesRegularExpression(
            '#^\s*<ul class="of-quick__list">\s*<li><a class="of-quick__link of-quick__link--selected"[^>]*aria-current="true"#',
            $nav[1],
            'the pick first, marked'
        );
        self::assertMatchesRegularExpression('#<span class="of-sr">[^<]+:</span>#', $nav[1], 'the filter name for screen readers');
        self::assertStringContainsString('data-of-link', $nav[1]);
    }

    private function mark(bool $quick): void
    {
        $repository = Bootstrap::getObjectManager()->get(FilterConfigRepositoryInterface::class);
        $repository->save($repository->getByCode(M1Catalog::COLOR)->setQuickBar($quick));
    }
}
