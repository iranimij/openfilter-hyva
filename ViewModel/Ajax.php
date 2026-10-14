<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\ViewModel;

use Iranimij\OpenFilter\Model\Config;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Whether the storefront gets the AJAX component (links work as plain navigation without it).
 */
class Ajax implements ArgumentInterface
{
    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled() && $this->config->isAjaxEnabled();
    }
}
