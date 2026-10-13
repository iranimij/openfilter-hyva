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
use Magento\Framework\View\DesignInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;

/**
 * A layered category page on Hyvä with OpenFilter's templates: the three regions, a real link for every option
 * (also in closed groups), no inline styles or handlers; switched off, the page keeps Hyvä's stock templates.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class CategoryPageTest extends AbstractController
{
    /** @var array{category: int, store: int, options: array<string, array<string, int>>} */
    private static array $catalog;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$catalog = M1Catalog::create();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $theme = Bootstrap::getObjectManager()->get(ThemeCollectionFactory::class)->create()
            ->addFieldToFilter('area', 'frontend')->addFieldToFilter('theme_path', 'Hyva/default')->getFirstItem();
        self::assertNotEmpty($theme->getId(), 'Hyvä default theme registered');
        // The frontend area loads its design before setUp, so the store's theme setting comes too late here.
        Bootstrap::getObjectManager()->get(DesignInterface::class)->setDesignTheme('Hyva/default', 'frontend');
    }

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

        self::assertSame(3, substr_count($filters, '<details class="of-group'), 'colour, size, material');
        foreach (array_keys(M1Catalog::OPTIONS) as $code) {
            self::assertMatchesRegularExpression(
                '#data-of-group="' . $code . '".*?<a class="of-option[^"]*"\s+href="[^"]*[?&]' . $code . '=#s',
                $filters,
                "$code options link to filtered URLs"
            );
        }
        preg_match_all('#<details class="of-group[^>]*>#', $filters, $groups);
        self::assertSame(
            [true, false, false],
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

    private function config(string $path, string $value): void
    {
        Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class)->setValue($path, $value, 'store', 'default');
    }

    /**
     * @param array<string, string> $query
     */
    private function page(array $query): string
    {
        $this->getRequest()->setMethod('GET')->setQueryValue($query);
        $this->dispatch('catalog/category/view/id/' . self::$catalog['category']);
        self::assertSame(200, $this->getResponse()->getHttpResponseCode());

        return (string) $this->getResponse()->getBody();
    }

    /**
     * Markup of one region: from its opening tag to the next region (or the end).
     */
    private function region(string $html, string $id): string
    {
        $start = strpos($html, 'id="' . $id . '"');
        self::assertNotFalse($start, "#$id present");
        $next = preg_match('#id="of-(?:filters|state|results)"#', $html, $m, PREG_OFFSET_CAPTURE, $start + 10)
            ? $m[0][1] : strlen($html);

        return substr($html, $start, $next - $start);
    }
}
