<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\ViewModel;

use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Products found on the page, read after the product list rendered: counting earlier runs the search before
 * Magento's toolbar sets the sort order and the page, and the list then shows every product unsorted (search pages).
 */
class ResultCount implements ArgumentInterface
{
    /**
     * @param LayerResolver $layerResolver
     */
    public function __construct(
        private readonly LayerResolver $layerResolver
    ) {
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return (int) $this->layerResolver->get()->getProductCollection()->getSize();
    }
}
