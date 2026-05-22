<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;

class ReservationAllocationStrategy extends FifoAllocationStrategy
{
    protected function orderedLayers(Collection $layers, MovementLineDTO $line): Collection
    {
        $reservedLayerIds = $line->metadata['reserved_layer_ids'] ?? [];
        if (!is_array($reservedLayerIds) || $reservedLayerIds === []) {
            return parent::orderedLayers($layers, $line);
        }

        $reservedSet = array_flip(array_map('intval', $reservedLayerIds));

        return $layers
            ->sortBy(fn ($layer): array => [
                array_key_exists((int) $layer->id, $reservedSet) ? 0 : 1,
                (string) ($layer->layer_date ?? ''),
                (int) $layer->id,
            ])
            ->values();
    }
}
