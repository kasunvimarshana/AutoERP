<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines\Strategies;

use Modules\Inventory\Application\Contracts\Strategies\AllocationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryAllocationMethod;

final class FifoAllocationStrategy implements AllocationStrategyInterface
{
    public function method(): string
    {
        return InventoryAllocationMethod::FIFO;
    }

    public function allocate(array $context): array
    {
        $requestedQuantity = max(0.0, (float) ($context['requested_quantity'] ?? 0));
        $stockLevels = is_array($context['stock_levels'] ?? null) ? $context['stock_levels'] : [];

        $remaining = $requestedQuantity;
        $allocations = [];

        foreach ($stockLevels as $stockLevel) {
            if (! is_array($stockLevel) || $remaining <= 0.0) {
                continue;
            }

            $available = max(
                0.0,
                (float) ($stockLevel['quantity_on_hand'] ?? 0) - (float) ($stockLevel['quantity_reserved'] ?? 0)
            );
            if ($available <= 0.0) {
                continue;
            }

            $allocated = min($remaining, $available);
            $remaining -= $allocated;

            $allocations[] = [
                'stock_level_id' => $stockLevel['id'] ?? null,
                'allocated_quantity' => round($allocated, 4),
                'item_id' => $stockLevel['item_id'] ?? null,
                'variant_id' => $stockLevel['variant_id'] ?? null,
                'warehouse_id' => $stockLevel['warehouse_id'] ?? null,
                'location_id' => $stockLevel['location_id'] ?? null,
                'batch_id' => $stockLevel['batch_id'] ?? null,
                'serial_id' => $stockLevel['serial_id'] ?? null,
                'unit_cost' => round((float) ($stockLevel['unit_cost'] ?? 0), 4),
            ];
        }

        return [
            'requested_quantity' => round($requestedQuantity, 4),
            'allocated_quantity' => round($requestedQuantity - $remaining, 4),
            'unallocated_quantity' => round($remaining, 4),
            'allocations' => $allocations,
        ];
    }
}
