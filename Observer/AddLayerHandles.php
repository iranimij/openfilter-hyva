<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Observer;

use Hyva\Theme\Service\CurrentTheme;
use Iranimij\OpenFilter\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;

/**
 * OpenFilter's templates and regions come through their own layout handles, added only on layered pages of a Hyvä
 * theme while OpenFilter is enabled. Switched off, or stepping aside for another layered navigation, the page keeps
 * the theme's stock templates.
 */
class AddLayerHandles implements ObserverInterface
{
    /** Shared by every OpenFilter page (stylesheet). */
    public const HANDLE = 'openfilter_hyva_layer';

    /** Page handle => OpenFilter handle. */
    private const PAGES = [
        'catalog_category_view_type_layered' => 'openfilter_hyva_category',
        'catalogsearch_result_index' => 'openfilter_hyva_search',
    ];

    /**
     * @param CurrentTheme $theme
     * @param Config $config
     */
    public function __construct(
        private readonly CurrentTheme $theme,
        private readonly Config $config
    ) {
    }

    /**
     * Add OpenFilter's handles on layered category and search pages.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $layout = $observer->getData('layout');
        if (!$layout instanceof LayoutInterface || !$this->theme->isHyva() || !$this->config->isEnabled()) {
            return;
        }
        $update = $layout->getUpdate();
        $handles = $update->getHandles();
        foreach (self::PAGES as $page => $handle) {
            if (in_array($page, $handles, true)) {
                $update->addHandle(self::HANDLE);
                $update->addHandle($handle);
            }
        }
    }
}
