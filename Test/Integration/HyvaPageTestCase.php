<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Integration;

use Iranimij\OpenFilter\Test\Integration\Catalog\M1Catalog;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\View\DesignInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;

/**
 * Storefront pages of OpenFilter's M1 test catalog rendered with the Hyvä default theme. One dispatch per test.
 */
abstract class HyvaPageTestCase extends AbstractController
{
    /** @var array{category: int, store: int, options: array<string, array<string, int>>} */
    protected static ?array $catalog = null;

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

    protected function config(string $path, string $value): void
    {
        Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class)->setValue($path, $value, 'store', 'default');
    }

    /**
     * @param array<string, string> $query
     */
    protected function page(array $query): string
    {
        $this->getRequest()->setMethod('GET')->setQueryValue($query);
        $this->dispatch('catalog/category/view/id/' . self::$catalog['category']);
        self::assertSame(200, $this->getResponse()->getHttpResponseCode());

        return (string) $this->getResponse()->getBody();
    }

    /**
     * Markup of one region: from its opening tag to the next region (or the end).
     */
    protected function region(string $html, string $id): string
    {
        $start = strpos($html, 'id="' . $id . '"');
        self::assertNotFalse($start, "#$id present");
        $next = preg_match('#id="of-(?:filters|state|results)"#', $html, $m, PREG_OFFSET_CAPTURE, $start + 10)
            ? $m[0][1] : strlen($html);

        return substr($html, $start, $next - $start);
    }

    /**
     * Markup of one filter group: from its details tag to the next group (or the end of the filter region).
     */
    protected function group(string $html, string $code): string
    {
        $start = strpos($html, 'data-of-group="' . $code . '"');
        self::assertNotFalse($start, "group $code");
        $ends = array_filter([strpos($html, '<details class="of-group', $start), strpos($html, '</nav>', $start)]);

        return substr($html, $start, ($ends === [] ? strlen($html) : min($ends)) - $start);
    }
}
