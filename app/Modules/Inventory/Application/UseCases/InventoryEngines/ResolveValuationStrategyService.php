<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines;

use Illuminate\Contracts\Container\Container;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Strategies\ValuationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Modules\Inventory\Domain\Constants\InventoryValuationMethod;

final class ResolveValuationStrategyService
{
    public function __construct(private readonly Container $container)
    {
    }

    public function execute(string $method): Result
    {
        $normalizedMethod = strtolower(trim($method));
        if (! in_array($normalizedMethod, InventoryValuationMethod::all(), true)) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_STRATEGY,
                sprintf('Unsupported valuation method: %s', $method),
            ));
        }

        $strategyMap = (array) config('inventory.engines.valuation_strategy_map', []);
        $strategyClass = $strategyMap[$normalizedMethod] ?? null;

        if (! is_string($strategyClass) || $strategyClass === '') {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_STRATEGY,
                sprintf('Valuation strategy is not configured for method: %s', $normalizedMethod),
            ));
        }

        $strategy = $this->container->make($strategyClass);
        if (! $strategy instanceof ValuationStrategyInterface) {
            return Result::failure(new Error(
                InventoryErrorCode::INVALID_STRATEGY,
                sprintf('Configured valuation strategy does not implement contract: %s', $strategyClass),
            ));
        }

        return Result::success($strategy);
    }
}
