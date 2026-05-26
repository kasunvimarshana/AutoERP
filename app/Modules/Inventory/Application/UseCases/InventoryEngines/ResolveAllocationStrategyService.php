<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines;

use Illuminate\Contracts\Container\Container;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Strategies\AllocationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryAllocationMethod;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;

final class ResolveAllocationStrategyService
{
    public function __construct(private readonly Container $container)
    {
    }

    public function execute(string $method): Result
    {
        $normalizedMethod = strtolower(trim($method));
        if (! in_array($normalizedMethod, InventoryAllocationMethod::all(), true)) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_STRATEGY,
                sprintf('Unsupported allocation method: %s', $method),
            ));
        }

        $strategyMap = (array) config('inventory.engines.allocation_strategy_map', []);
        $strategyClass = $strategyMap[$normalizedMethod] ?? null;

        if (! is_string($strategyClass) || $strategyClass === '') {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_STRATEGY,
                sprintf('Allocation strategy is not configured for method: %s', $normalizedMethod),
            ));
        }

        $strategy = $this->container->make($strategyClass);
        if (! $strategy instanceof AllocationStrategyInterface) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_STRATEGY,
                sprintf('Configured allocation strategy does not implement contract: %s', $strategyClass),
            ));
        }

        return Result::success($strategy);
    }
}
