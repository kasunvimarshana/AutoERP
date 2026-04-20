<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Allocation;

use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Entities\AllocationResult;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;

/**
 * LIFO allocation — newest layers first.
 *
 * The caller must provide layers ordered by layer_date DESC
 * (i.e. from CostLayerRepositoryInterface::findOpenLayersNewestFirst).
 */
class LifoAllocationStrategy extends FifoAllocationStrategy implements AllocationStrategyInterface
{
    public function getStrategy(): string
    {
        return 'lifo';
    }

    /**
     * @param  InventoryCostLayer[]  $availableLayers  Pre-sorted newest → oldest
     */
    public function allocate(string $requiredQuantity, array $availableLayers, array $context = []): AllocationResult
    {
        return $this->allocateSequential($requiredQuantity, $availableLayers);
    }
}
