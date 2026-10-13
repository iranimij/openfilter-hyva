<?php
/**
 * Copyright © Iman Aboheydary. All rights reserved.
 * See LICENSE for license details (MIT).
 */

declare(strict_types=1);

namespace Iranimij\OpenFilterHyva\Test\Integration;

use Magento\Framework\Module\ModuleList;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ModuleEnabledTest extends TestCase
{
    public function testModuleOpenFilterAndTheSharedBaseAreEnabled(): void
    {
        $moduleList = Bootstrap::getObjectManager()->get(ModuleList::class);

        self::assertTrue($moduleList->has('Iranimij_Base'));
        self::assertTrue($moduleList->has('Iranimij_OpenFilter'));
        self::assertTrue($moduleList->has('Iranimij_OpenFilterHyva'));
    }
}
