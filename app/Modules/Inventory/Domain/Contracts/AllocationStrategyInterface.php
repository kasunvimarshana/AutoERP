<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Domain\Entities\AllocationResult;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;

/**
 * Strategy contract for allocating stock from available layers.
 *
 * Determines which cost layers (and by extension which batches / serials /
 * locations) are picked to satisfy a requested quantity.
 */
interface AllocationStrategyInterface
{
    /**
     * Return the identifier for this allocation strategy (e.g. 'fifo').
     */
    public function getStrategy(): string;

    /**
     * Allocate the requested quantity from the provided layers.
     *
     * Layers must already be pre-filtered to the correct tenant / product /
     * variant scope before calling this method.
     *
     * @param  string  $requiredQuantity  Decimal string (scale 6)
     * @param  InventoryCostLayer[]  $availableLayers  Open layers with remaining > 0
     * @param  array<string, mixed>  $context  Optional hints (e.g. manual_layer_ids, preferred_location_id)
     *
     * @throws InsufficientAvailableStockException
     */
    public function allocate(
        string $requiredQuantity,
        array $availableLayers,
        array $context = [],
    ): AllocationResult;
}
