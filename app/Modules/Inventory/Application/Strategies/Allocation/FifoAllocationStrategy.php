<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Allocation;

use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Entities\AllocationLine;
use Modules\Inventory\Domain\Entities\AllocationResult;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;

/**
 * FIFO allocation — oldest layers first.
 *
 * The caller is responsible for providing layers ordered by layer_date ASC
 * (i.e. from CostLayerRepositoryInterface::findOpenLayersOldestFirst).
 */
class FifoAllocationStrategy implements AllocationStrategyInterface
{
    public function getStrategy(): string
    {
        return 'fifo';
    }

    /**
     * @param  InventoryCostLayer[]  $availableLayers  Pre-sorted oldest → newest
     */
    public function allocate(string $requiredQuantity, array $availableLayers, array $context = []): AllocationResult
    {
        return $this->allocateSequential($requiredQuantity, $availableLayers);
    }

    /**
     * @param  InventoryCostLayer[]  $layers
     */
    protected function allocateSequential(string $requiredQuantity, array $layers): AllocationResult
    {
        $remaining = $requiredQuantity;
        $lines = [];
        $totalAllocated = '0.000000';

        foreach ($layers as $layer) {
            if (bccomp($remaining, '0.000000', 6) <= 0) {
                break;
            }

            $available = $layer->getQuantityRemaining();

            if (bccomp($available, '0.000000', 6) <= 0) {
                continue;
            }

            $allocated = bccomp($remaining, $available, 6) >= 0 ? $available : $remaining;

            $lines[] = new AllocationLine(
                costLayerId: (int) $layer->getId(),
                locationId: $layer->getLocationId(),
                batchId: $layer->getBatchId(),
                variantId: $layer->getVariantId(),
                allocatedQuantity: $allocated,
                unitCost: $layer->getUnitCost(),
            );

            $totalAllocated = bcadd($totalAllocated, $allocated, 6);
            $remaining = bcsub($remaining, $allocated, 6);
        }

        $fullyAllocated = bccomp($remaining, '0.000000', 6) <= 0;

        if (! $fullyAllocated) {
            throw new InsufficientAvailableStockException(
                sprintf(
                    'FIFO allocation failed: still need %s after exhausting all layers.',
                    $remaining,
                ),
            );
        }

        return new AllocationResult($lines, $totalAllocated, true);
    }
}
