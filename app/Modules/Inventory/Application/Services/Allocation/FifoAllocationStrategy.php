<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;

/**
 * First-In-First-Out allocation strategy.
 * Allocates from batch/lots in ascending order of creation date (oldest first).
 */
class FifoAllocationStrategy implements AllocationStrategyInterface
{
    public function allocate(Collection $batchLots, float $requiredQuantity): Collection
    {
        $sorted = $batchLots->sortBy('created_at');

        return $this->performAllocation($sorted, $requiredQuantity);
    }

    public function getName(): string
    {
        return 'fifo';
    }

    /**
     * Allocate sequentially from sorted batch/lots until quantity is fulfilled.
     *
     * @return Collection<int, array{batch_lot_id: string, quantity: float}>
     */
    protected function performAllocation(Collection $batchLots, float $required): Collection
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
