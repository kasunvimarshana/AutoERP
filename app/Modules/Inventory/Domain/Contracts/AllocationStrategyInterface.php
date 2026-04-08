<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Illuminate\Support\Collection;

interface AllocationStrategyInterface
{
    /**
     * Allocate the requested quantity from available batch/lot records.
     *
     * Returns a collection of allocations, each containing:
     *   - batch_lot_id: string
     *   - quantity: float   (quantity allocated from this batch/lot)
     *
     * Throws InsufficientStockException if total available < requested.
     *
     * @param  Collection  $batchLots  Available batch/lot records (Eloquent models or stdClass).
     * @param  float  $requiredQuantity  Total quantity to allocate.
     * @return Collection<int, array{batch_lot_id: string, quantity: float}>
     */
    public function allocate(Collection $batchLots, float $requiredQuantity): Collection;

    /**
     * Strategy name used for logging and configuration.
     */
    public function getName(): string;
}
