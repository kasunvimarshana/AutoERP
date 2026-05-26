<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines\Strategies;

use Modules\Inventory\Application\UseCases\InventoryEngines\Strategies\WeightedAverageValuationStrategy;
use Modules\Inventory\Application\Contracts\Strategies\ValuationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryValuationMethod;

final class StandardCostValuationStrategy implements ValuationStrategyInterface
{
    public function method(): string
    {
        return InventoryValuationMethod::STANDARD;
    }

    public function calculate(array $context): array
    {
        $requestedQuantity = max(0.0, (float) ($context['requested_quantity'] ?? 0));
        $standardCost = max(0.0, (float) ($context['standard_cost'] ?? 0));

        if ($standardCost <= 0.0) {
            $weighted = new WeightedAverageValuationStrategy();

            return $weighted->calculate($context);
        }

        return [
            'requested_quantity' => round($requestedQuantity, 4),
            'valued_quantity' => round($requestedQuantity, 4),
            'remaining_unvalued_quantity' => 0.0,
            'unit_cost' => round($standardCost, 4),
            'total_cost' => round($requestedQuantity * $standardCost, 4),
            'consumed_layers' => [],
        ];
    }
}
