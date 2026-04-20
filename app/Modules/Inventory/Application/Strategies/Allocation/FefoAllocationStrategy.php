<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Allocation;

use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Entities\AllocationResult;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;

/**
 * FEFO allocation — First Expired, First Out.
 *
 * The caller must provide layers ordered by batch expiry_date ASC
 * (i.e. from CostLayerRepositoryInterface::findOpenLayersByExpiryAsc).
 * Layers without expiry are placed last.
 */
class FefoAllocationStrategy extends FifoAllocationStrategy implements AllocationStrategyInterface
{
    public function getStrategy(): string
    {
        return 'fefo';
    }

    /**
     * @param  InventoryCostLayer[]  $availableLayers  Pre-sorted by expiry ASC
     */
    public function allocate(string $requiredQuantity, array $availableLayers, array $context = []): AllocationResult
    {
        return $this->allocateSequential($requiredQuantity, $availableLayers);
    }
}
