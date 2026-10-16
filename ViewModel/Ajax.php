<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\ViewModel;

use Iranimij\OpenFilter\Api\Data\FilterConfigInterface;
use Iranimij\OpenFilter\Model\Config;
use Iranimij\OpenFilter\Model\Config\FilterConfigProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Whether the storefront gets the AJAX component (links work as plain navigation without it).
 */
class Ajax implements ArgumentInterface
{
    /**
     * @param Config $config
     * @param FilterConfigProvider $filters
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly FilterConfigProvider $filters,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled() && $this->config->isAjaxEnabled();
    }

    /**
     * The 1.1 extras script (desktop "Show N products", dropdowns that load on change, the horizontal bar's
     * popovers, the H1 template after swaps) is printed only where a store
     * view uses one of them, so the default page keeps its script budget.
     *
     * @return bool
     */
    public function needsExtras(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        $storeId = (int) $this->storeManager->getStore()->getId();
        if ($this->config->getApplyMode($storeId) === Config::APPLY_BUTTON
            || $this->config->getLayout($storeId) === Config::LAYOUT_HORIZONTAL
            || $this->config->getH1Template($storeId) !== '') {
            return true;
        }
        foreach ($this->filters->getActive($storeId) as $filter) {
            if ($filter->getDisplayMode() === FilterConfigInterface::DISPLAY_DROPDOWN) {
                return true;
            }
        }

        return false;
    }

    /**
     * How shoppers see more products (1.2): Config::PAGINATION_PAGES (no script), _LOAD_MORE or _INFINITE.
     *
     * @return string
     */
    public function pagination(): string
    {
        return $this->isEnabled()
            ? $this->config->getPagination((int) $this->storeManager->getStore()->getId())
            : Config::PAGINATION_PAGES;
    }
}
