<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Services;

use Modules\Inventory\Domain\Contracts\AllocationStrategyContract;
use Modules\Inventory\Domain\Contracts\ValuationStrategyContract;
use Modules\Inventory\Domain\Enums\AllocationMethod;
use Modules\Inventory\Domain\Enums\ValuationMethod;
use Modules\Inventory\Domain\Exceptions\InventoryConfigurationException;

class StrategyResolverService
{
    public function resolveAllocation(AllocationMethod $method): AllocationStrategyContract
    {
        $map = config('inventory-engine.strategies.allocation', []);
        $class = $map[$method->value] ?? null;

        if (!is_string($class) || !class_exists($class)) {
            throw new InventoryConfigurationException("Allocation strategy not configured for {$method->value}");
        }

        $strategy = app($class);
        if (!$strategy instanceof AllocationStrategyContract) {
            throw new InventoryConfigurationException("{$class} must implement AllocationStrategyContract");
        }

        return $strategy;
    }

    public function resolveValuation(ValuationMethod $method): ValuationStrategyContract
    {
        $map = config('inventory-engine.strategies.valuation', []);
        $class = $map[$method->value] ?? null;

        if (!is_string($class) || !class_exists($class)) {
            throw new InventoryConfigurationException("Valuation strategy not configured for {$method->value}");
        }

        $strategy = app($class);
        if (!$strategy instanceof ValuationStrategyContract) {
            throw new InventoryConfigurationException("{$class} must implement ValuationStrategyContract");
        }

        return $strategy;
    }
}
