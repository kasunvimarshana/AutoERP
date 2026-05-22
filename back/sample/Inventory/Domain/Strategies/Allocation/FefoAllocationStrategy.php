<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;

class FefoAllocationStrategy extends AbstractLayerAllocationStrategy
{
    protected function orderedLayers(Collection $layers, MovementLineDTO $line): Collection
    {
        return $layers->sortBy(function ($layer): array {
            $expiry = $layer->batch?->expiry_date;

            return [
                $expiry ? (string) $expiry : '9999-12-31',
                (string) ($layer->layer_date ?? ''),
                (int) $layer->id,
            ];
        })->values();
    }
}
