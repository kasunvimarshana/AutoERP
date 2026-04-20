<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Allocation;

use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Entities\AllocationResult;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;

/**
 * Manual allocation.
 *
 * The caller explicitly provides the layer IDs to consume via the context
 * key 'manual_layer_ids' (array of ints).  Layers are consumed in the order
 * specified by the caller.
 *
 * Throws InsufficientAvailableStockException if the provided layers do not
 * satisfy the required quantity.
 */
class ManualAllocationStrategy extends FifoAllocationStrategy implements AllocationStrategyInterface
{
    public function getStrategy(): string
    {
        return 'manual';
    }

    /**
     * @param  InventoryCostLayer[]  $availableLayers
     * @param  array<string, mixed>  $context  Must contain 'manual_layer_ids' (int[])
     */
    public function allocate(string $requiredQuantity, array $availableLayers, array $context = []): AllocationResult
    {
        $manualIds = isset($context['manual_layer_ids']) && is_array($context['manual_layer_ids'])
            ? array_map('intval', $context['manual_layer_ids'])
            : [];

        if (empty($manualIds)) {
            return $this->allocateSequential($requiredQuantity, $availableLayers);
        }

        $idIndex = array_flip($manualIds);
        $ordered = [];

        foreach ($manualIds as $layerId) {
            foreach ($availableLayers as $layer) {
                if ($layer->getId() === $layerId) {
                    $ordered[] = $layer;
                    break;
                }
            }
        }

        return $this->allocateSequential($requiredQuantity, $ordered);
    }
}
