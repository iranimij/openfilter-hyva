<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Integration;

use Iranimij\OpenFilter\Api\Data\FilterConfigInterface;
use Iranimij\OpenFilter\Api\FilterConfigRepositoryInterface;
use Iranimij\OpenFilter\Model\Config;
use Iranimij\OpenFilter\Test\Integration\Catalog\M1Catalog;
use Iranimij\OpenFilter\Test\Integration\Catalog\M5Categories;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * M5 S6 storefront modes on the Hyvä templates: nothing changes with the 1.0 defaults (no extras script, no new
 * markup); dropdown as a GET form, "all must match" and tooltip hint, category tree depth, horizontal bar above the
 * results in one column, desktop apply bar and sticky sidebar. One dispatch per test.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class StorefrontModesTest extends HyvaPageTestCase
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
        $this->repository->saveTooltip(M1Catalog::COLOR, 0, null);
        $this->repository->resetToDefault(FilterConfigInterface::BUILTIN_CATEGORY);
        parent::tearDown();
    }

    public function testThe10DefaultsPrintNoneOfThe11Parts(): void
    {
        $html = $this->page([]);

        foreach (['of-drawer--horizontal', 'of-drawer--sticky', 'data-of-apply-bar', 'of-group__hint', 'of-dropdown',
            'window.openFilterStage = ', 'of-more-config', 'of-quick', 'of-insights'] as $part) {
            self::assertStringNotContainsString($part, $html, $part);
        }
    }

    public function testMoreProductsPrintTheirScriptOnlyWhenChosen(): void
    {
        $this->config('openfilter/display/pagination', 'infinite');

        $html = $this->page([]);

        self::assertMatchesRegularExpression('#<div id="of-more-config" hidden data-mode="infinite"#', $html);
        self::assertSame(1, substr_count($html, "document.getElementById('of-more-config')"), 'one static script');
    }

    public function testInsightsPrintTheirBeaconOnlyWhenOn(): void
    {
        $this->config('openfilter/insights/enabled', '1');

        $html = $this->page([M1Catalog::COLOR => 'red']);

        self::assertMatchesRegularExpression('#<div id="of-insights" hidden data-url="[^"]+/openfilter/insights/collect/?"#', $html);
        $red = self::$catalog['options'][M1Catalog::COLOR]['Red'];
        self::assertSame(1, preg_match('#data-payload="([^"]+)"#', $html, $payload));
        self::assertSame(
            ['s' => 'default', 'f' => [M1Catalog::COLOR => [(string) $red]]],
            array_diff_key(json_decode(html_entity_decode($payload[1], ENT_QUOTES | ENT_HTML5), true), ['c' => 0]),
            'the store view and the active filters by option id, nothing else'
        );
        self::assertSame(1, substr_count($html, "doc.getElementById('of-insights')"), 'one static script');
    }

    public function testADropdownIsAGetFormThatKeepsTheOtherFilters(): void
    {
        $this->repository->save($this->repository->getByCode(M1Catalog::COLOR)->setDisplayMode(FilterConfigInterface::DISPLAY_DROPDOWN));

        $html = $this->page([M1Catalog::COLOR => 'red', M1Catalog::SIZE => 'm']);
        $group = $this->group($html, M1Catalog::COLOR);

        self::assertMatchesRegularExpression('#<form class="of-dropdown" method="get" action="[^"?]+">#', $group);
        self::assertMatchesRegularExpression('#<input type="hidden" name="' . M1Catalog::SIZE . '" value="m">#', $group);
        self::assertStringNotContainsString('type="hidden" name="' . M1Catalog::COLOR . '"', $group, 'the select sends the colour');
        self::assertMatchesRegularExpression('#<select class="of-dropdown__select"[^>]*name="' . M1Catalog::COLOR . '"#', $group);
        $red = self::$catalog['options'][M1Catalog::COLOR]['Red'];
        self::assertMatchesRegularExpression('#<option value="' . $red . '" selected>\s*Red \(\d+\)#', $group);
        self::assertStringContainsString('window.openFilterStage = ', $html, 'the extras script loads on change');
    }

    public function testMatchAllAndTheTooltipShowAtTheTopOfTheGroup(): void
    {
        $this->repository->save($this->repository->getByCode(M1Catalog::COLOR)->setLogic(FilterConfigInterface::LOGIC_AND));
        $this->repository->saveTooltip(M1Catalog::COLOR, 0, 'Pick every colour the product has.');

        $group = $this->group($this->page([]), M1Catalog::COLOR);

        self::assertMatchesRegularExpression(
            '#<div class="of-group__body"[^>]*>\s*<p class="of-group__hint">\s*<span class="of-group__match">Products have all picked values\.</span>\s*</p>\s*'
            . '<details class="of-about">\s*<summary>About [^<]+</summary>\s*<p class="of-group__hint">Pick every colour the product has\.</p>#',
            $group,
            'one line for "all"; the tooltip opens on demand'
        );
    }

    public function testATooltipAloneStaysInView(): void
    {
        $this->repository->saveTooltip(M1Catalog::COLOR, 0, 'Pick a colour.');

        $group = $this->group($this->page([]), M1Catalog::COLOR);

        self::assertMatchesRegularExpression('#<div class="of-group__body"[^>]*>\s*<p class="of-group__hint">Pick a colour\.</p>#', $group);
        self::assertStringNotContainsString('of-about', $group);
    }

    public function testCategoryTreeOptionsCarryTheirDepth(): void
    {
        $tree = M5Categories::create(self::$catalog['category']);
        $this->repository->save($this->repository->getByCode(FilterConfigInterface::BUILTIN_CATEGORY)->setDisplayMode(FilterConfigInterface::DISPLAY_TREE));

        $group = $this->group($this->page([]), FilterConfigInterface::BUILTIN_CATEGORY);

        self::assertMatchesRegularExpression('#<li class="of-options__item of-options__item--depth-1"><a [^>]*data-of-value="' . $tree[M5Categories::TOPS] . '"#', $group);
        self::assertMatchesRegularExpression('#<li class="of-options__item of-options__item--depth-2"><a [^>]*data-of-value="' . $tree[M5Categories::TEES] . '"#', $group);
    }

    public function testTheHorizontalBarSitsAboveTheResultsInOneColumnWithClosedGroups(): void
    {
        $this->config(Config::XML_PATH_LAYOUT, Config::LAYOUT_HORIZONTAL);

        $html = $this->page([]);

        self::assertMatchesRegularExpression('#<dialog id="of-drawer" class="[^"]*of-drawer--horizontal#', $html);
        self::assertLessThan(strpos($html, 'id="of-results"'), strpos($html, 'id="of-drawer"'));
        self::assertMatchesRegularExpression('#<body[^>]*class="[^"]*page-layout-1column#', $html);
        self::assertStringNotContainsString('<details class="of-group of-group--list"' . "\n" . '         data-of-group="' . M1Catalog::COLOR . '" open', $html);
        self::assertDoesNotMatchRegularExpression('#<details class="of-group[^>]*\sopen[\s>]#', $this->region($html, 'of-filters'));
        self::assertStringContainsString('window.openFilterStage = ', $html);
    }

    public function testDesktopApplyModeAndStickySidebar(): void
    {
        $this->config(Config::XML_PATH_APPLY_MODE, Config::APPLY_BUTTON);
        $this->config(Config::XML_PATH_STICKY, '1');

        $html = $this->page([]);

        self::assertMatchesRegularExpression('#<dialog id="of-drawer" class="[^"]*of-drawer--sticky#', $html);
        self::assertStringContainsString('<div class="of-apply" data-of-apply-bar hidden>', $html);
        self::assertStringContainsString('data-of-apply-show', $html);
        self::assertStringContainsString('window.openFilterStage = ', $html);
    }
}
