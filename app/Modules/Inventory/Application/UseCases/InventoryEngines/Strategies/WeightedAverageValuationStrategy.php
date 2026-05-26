<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines\Strategies;

use Modules\Inventory\Application\Contracts\Strategies\ValuationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryValuationMethod;

final class WeightedAverageValuationStrategy implements ValuationStrategyInterface
{
    public function method(): string
    {
        return InventoryValuationMethod::WEIGHTED_AVERAGE;
    }

    public function calculate(array $context): array
    {
        $requestedQuantity = max(0.0, (float) ($context['requested_quantity'] ?? 0));
        $layers = is_array($context['layers'] ?? null) ? $context['layers'] : [];

        $weightedCost = 0.0;
        $weightedQuantity = 0.0;

        foreach ($layers as $layer) {
            if (! is_array($layer)) {
                continue;
            }

            $quantity = max(0.0, (float) ($layer['quantity_remaining'] ?? 0));
            $unitCost = max(0.0, (float) ($layer['unit_cost'] ?? 0));

            if ($quantity <= 0.0) {
                continue;
            }

            $weightedQuantity += $quantity;
            $weightedCost += $quantity * $unitCost;
        }

        if ($weightedQuantity <= 0.0) {
            $stockLevels = is_array($context['stock_levels'] ?? null) ? $context['stock_levels'] : [];
            foreach ($stockLevels as $stockLevel) {
                if (! is_array($stockLevel)) {
                    continue;
                }

                $availableQuantity = max(
                    0.0,
                    (float) ($stockLevel['quantity_on_hand'] ?? 0) - (float) ($stockLevel['quantity_reserved'] ?? 0)
                );
                $unitCost = max(0.0, (float) ($stockLevel['unit_cost'] ?? 0));

                if ($availableQuantity <= 0.0) {
                    continue;
                }

                $weightedQuantity += $availableQuantity;
                $weightedCost += $availableQuantity * $unitCost;
            }
        }

        if ($weightedQuantity <= 0.0) {
            return [
                'requested_quantity' => round($requestedQuantity, 4),
                'valued_quantity' => 0.0,
                'remaining_unvalued_quantity' => round($requestedQuantity, 4),
                'unit_cost' => 0.0,
                'total_cost' => 0.0,
                'consumed_layers' => [],
            ];
        }

        $unitCost = round($weightedCost / $weightedQuantity, 4);
        $valuedQuantity = round(min($requestedQuantity, $weightedQuantity), 4);
        $totalCost = round($valuedQuantity * $unitCost, 4);

        return [
            'requested_quantity' => round($requestedQuantity, 4),
            'valued_quantity' => $valuedQuantity,
            'remaining_unvalued_quantity' => round($requestedQuantity - $valuedQuantity, 4),
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'consumed_layers' => [],
        ];
    }
}
