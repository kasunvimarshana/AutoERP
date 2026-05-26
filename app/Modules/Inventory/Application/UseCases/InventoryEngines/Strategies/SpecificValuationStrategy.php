<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines\Strategies;

use Modules\Inventory\Application\Contracts\Strategies\ValuationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryValuationMethod;

final class SpecificValuationStrategy implements ValuationStrategyInterface
{
    public function method(): string
    {
        return InventoryValuationMethod::SPECIFIC;
    }

    public function calculate(array $context): array
    {
        $requestedQuantity = max(0.0, (float) ($context['requested_quantity'] ?? 0));
        $layers = is_array($context['layers'] ?? null) ? $context['layers'] : [];

        if ($layers === []) {
            return [
                'requested_quantity' => round($requestedQuantity, 4),
                'valued_quantity' => 0.0,
                'remaining_unvalued_quantity' => round($requestedQuantity, 4),
                'unit_cost' => 0.0,
                'total_cost' => 0.0,
                'consumed_layers' => [],
            ];
        }

        $layer = is_array($layers[0]) ? $layers[0] : [];
        $available = max(0.0, (float) ($layer['quantity_remaining'] ?? 0));
        $valuedQuantity = round(min($requestedQuantity, $available), 4);
        $unitCost = round((float) ($layer['unit_cost'] ?? 0), 4);

        return [
            'requested_quantity' => round($requestedQuantity, 4),
            'valued_quantity' => $valuedQuantity,
            'remaining_unvalued_quantity' => round($requestedQuantity - $valuedQuantity, 4),
            'unit_cost' => $unitCost,
            'total_cost' => round($valuedQuantity * $unitCost, 4),
            'consumed_layers' => [
                [
                    'layer_id' => $layer['id'] ?? null,
                    'quantity' => $valuedQuantity,
                    'unit_cost' => $unitCost,
                    'cost' => round($valuedQuantity * $unitCost, 4),
                ],
            ],
        ];
    }
}
