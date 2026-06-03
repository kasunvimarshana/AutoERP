<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\InventoryEngines;

use Modules\Core\Application\Results\Result;

interface InventoryStrategyRegistryInterface
{
    public function resolveValuation(string $method): Result;

    public function resolveAllocation(string $method): Result;

    /**
     * @return list<string>
     */
    public function valuationMethods(): array;

    /**
     * @return list<string>
     */
    public function allocationMethods(): array;
}
