<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;

class FifoAllocationStrategy extends AbstractLayerAllocationStrategy
{
    protected function orderedLayers(Collection $layers, MovementLineDTO $line): Collection
    {
        return $layers->sortBy(fn ($layer): array => [
            (string) ($layer->layer_date ?? ''),
            (int) $layer->id,
        ])->values();
    }
}
