<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;

/**
 * Last-In-First-Out allocation strategy.
 * Allocates from batch/lots in descending order of creation date (newest first).
 */
class LifoAllocationStrategy implements AllocationStrategyInterface
{
    public function allocate(Collection $batchLots, float $requiredQuantity): Collection
    {
        $sorted = $batchLots->sortByDesc('created_at');

        return $this->performAllocation($sorted, $requiredQuantity);
    }

    public function getName(): string
    {
        return 'lifo';
    }

    /**
     * @return Collection<int, array{batch_lot_id: string, quantity: float}>
     */
    private function performAllocation(Collection $batchLots, float $required): Collection
    {
        $allocations = collect();
        $remaining   = $required;

        foreach ($batchLots as $lot) {
            if ($remaining <= 0.0) {
                break;
            }

            $available = (float) $lot->quantity;
            if ($available <= 0.0) {
                continue;
            }

            $toAllocate = min($available, $remaining);
            $allocations->push([
                'batch_lot_id' => $lot->id,
                'quantity'     => $toAllocate,
            ]);

            $remaining -= $toAllocate;
        }

        if ($remaining > 0.0001) {
            throw new InsufficientStockException('batch_lot', $required, $required - $remaining);
        }

        return $allocations;
    }
}
