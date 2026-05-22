<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;

class BatchAllocationStrategy extends FifoAllocationStrategy
{
    protected function orderedLayers(Collection $layers, MovementLineDTO $line): Collection
    {
        $filtered = $line->batchId !== null
            ? $layers->where('batch_id', $line->batchId)
            : $layers;

        return parent::orderedLayers($filtered->values(), $line);
    }
}
