<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\InventoryEngines\Strategies;

use Modules\Inventory\Application\Contracts\Strategies\AllocationStrategyInterface;
use Modules\Inventory\Domain\Constants\InventoryDimension;

final class ConfigurableSequenceAllocationStrategy implements AllocationStrategyInterface
{
    public function __construct(private readonly string $method = 'configured_sequence') {}

    public function method(): string
    {
        return strtolower(trim($this->method));
    }

    public function allocate(array $context): array
    {
        $precision = max(0, (int) ($context['precision'] ?? config('inventory.engines.precision', 4)));
        $requestedQuantity = max(0.0, (float) ($context['requested_quantity'] ?? 0));
        $stockLevels = is_array($context['stock_levels'] ?? null) ? $context['stock_levels'] : [];
        $allocationDimensions = $this->allocationDimensions();

        $remaining = $requestedQuantity;
        $allocations = [];

        foreach ($stockLevels as $stockLevel) {
            if (! is_array($stockLevel) || $remaining <= 0.0) {
                continue;
            }

            $available = max(
                0.0,
                (float) ($stockLevel['quantity_on_hand'] ?? 0)
                - (float) ($stockLevel['quantity_reserved'] ?? 0)
                - (float) ($stockLevel['quantity_blocked'] ?? 0)
            );

            if ($available <= 0.0) {
                continue;
            }

            $allocated = min($remaining, $available);
            $remaining -= $allocated;

            $allocation = [
                'stock_level_id' => $stockLevel['id'] ?? null,
                'allocated_quantity' => round($allocated, $precision),
                'available_quantity' => round($available, $precision),
                'unit_cost' => round((float) ($stockLevel['unit_cost'] ?? 0), $precision),
            ];

            foreach ($allocationDimensions as $dimension) {
                $allocation[$dimension] = $stockLevel[$dimension] ?? null;
            }

            $allocations[] = $allocation;
        }

        return [
            'requested_quantity' => round($requestedQuantity, $precision),
            'allocated_quantity' => round($requestedQuantity - $remaining, $precision),
            'unallocated_quantity' => round($remaining, $precision),
            'allocations' => $allocations,
        ];
    }

    /**
     * @return list<string>
     */
    private function allocationDimensions(): array
    {
        $configured = config('inventory.engines.allocation.allocation_dimensions', InventoryDimension::all());
        if (! is_array($configured)) {
            return InventoryDimension::all();
        }

        $dimensions = [];
        foreach ($configured as $dimension) {
            if (is_string($dimension) && trim($dimension) !== '') {
                $dimensions[] = $dimension;
            }
        }

        return $dimensions === [] ? InventoryDimension::all() : array_values(array_unique($dimensions));
    }
}
