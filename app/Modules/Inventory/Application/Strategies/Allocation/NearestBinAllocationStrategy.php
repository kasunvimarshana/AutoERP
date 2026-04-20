<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Allocation;

use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Entities\AllocationResult;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;

/**
 * Nearest Bin allocation.
 *
 * Picks layers from the location that is physically closest to a reference
 * location, identified via the context key 'preferred_location_id'.
 *
 * If no preferred location is provided this strategy falls back to FIFO order.
 *
 * NOTE: True nearest-bin optimisation requires a distance matrix provided
 * externally. This implementation uses the preferred_location_id as a
 * priority hint: layers at that location are consumed first, then others.
 */
class NearestBinAllocationStrategy extends FifoAllocationStrategy implements AllocationStrategyInterface
{
    public function getStrategy(): string
    {
        return 'nearest_bin';
    }

    /**
     * @param  InventoryCostLayer[]  $availableLayers
     * @param  array<string, mixed>  $context  May contain 'preferred_location_id' (int)
     */
    public function allocate(string $requiredQuantity, array $availableLayers, array $context = []): AllocationResult
    {
        $preferredLocationId = isset($context['preferred_location_id'])
            ? (int) $context['preferred_location_id']
            : null;

        if ($preferredLocationId === null) {
            return $this->allocateSequential($requiredQuantity, $availableLayers);
        }

        $preferred = [];
        $others = [];

        foreach ($availableLayers as $layer) {
            if ($layer->getLocationId() === $preferredLocationId) {
                $preferred[] = $layer;
            } else {
                $others[] = $layer;
            }
        }

        $sorted = array_merge($preferred, $others);

        return $this->allocateSequential($requiredQuantity, $sorted);
    }
}
