<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Modules\Inventory\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Enums\AllocationMethod;

final class InventoryAllocationStrategyResolver
{
    public function __construct(private readonly Container $container) {}

    public function resolve(AllocationMethod $method): AllocationStrategyInterface
    {
        $class = config('inventory.allocation.strategies.'.$method->value);
        if (! is_string($class) || $class === '') {
            throw new InvalidArgumentException("Inventory allocation strategy [{$method->value}] is not configured.");
        }

        $strategy = $this->container->make($class);
        if (! $strategy instanceof AllocationStrategyInterface) {
            throw new InvalidArgumentException("Inventory allocation strategy [{$class}] is invalid.");
        }

        return $strategy;
    }
}
