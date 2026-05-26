<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines\Strategies;

use Modules\Inventory\Application\Contracts\Strategies\ValuationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryValuationMethod;

final class FifoValuationStrategy implements ValuationStrategyInterface
{
    public function method(): string
    {
        return InventoryValuationMethod::FIFO;
    }

    public function calculate(array $context): array
    {
        $requestedQuantity = max(0.0, (float) ($context['requested_quantity'] ?? 0));
        $layers = is_array($context['layers'] ?? null) ? $context['layers'] : [];

        $remaining = $requestedQuantity;
        $totalCost = 0.0;
        $consumed = [];

        foreach ($layers as $layer) {
            if (! is_array($layer) || $remaining <= 0.0) {
                continue;
            }

            $available = max(0.0, (float) ($layer['quantity_remaining'] ?? 0));
            if ($available <= 0.0) {
                continue;
            }

            $unitCost = (float) ($layer['unit_cost'] ?? 0);
            $consumedQuantity = min($remaining, $available);
            $lineCost = round($consumedQuantity * $unitCost, 4);

            $consumed[] = [
                'layer_id' => $layer['id'] ?? null,
                'quantity' => round($consumedQuantity, 4),
                'unit_cost' => round($unitCost, 4),
                'cost' => $lineCost,
            ];

            $totalCost += $lineCost;
            $remaining -= $consumedQuantity;
        }

        $valuedQuantity = round($requestedQuantity - $remaining, 4);
        $effectiveUnitCost = $valuedQuantity > 0 ? round($totalCost / $valuedQuantity, 4) : 0.0;

        return [
            'requested_quantity' => round($requestedQuantity, 4),
            'valued_quantity' => $valuedQuantity,
            'remaining_unvalued_quantity' => round($remaining, 4),
            'unit_cost' => $effectiveUnitCost,
            'total_cost' => round($totalCost, 4),
            'consumed_layers' => $consumed,
        ];
    }
}
