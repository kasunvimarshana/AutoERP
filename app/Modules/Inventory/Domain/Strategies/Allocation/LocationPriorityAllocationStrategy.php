<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;

class LocationPriorityAllocationStrategy extends FifoAllocationStrategy
{
    protected function orderedLayers(Collection $layers, MovementLineDTO $line): Collection
    {
        $priorities = $line->metadata['location_priority'] ?? [];
        if (!is_array($priorities) || $priorities === []) {
            return parent::orderedLayers($layers, $line);
        }

        $priorityIndex = array_flip(array_map('intval', $priorities));

        return $layers
            ->sortBy(function ($layer) use ($priorityIndex): array {
                $locationId = $layer->location_id !== null ? (int) $layer->location_id : -1;
                $rank = $priorityIndex[$locationId] ?? 999999;

                return [$rank, (string) ($layer->layer_date ?? ''), (int) $layer->id];
            })
            ->values();
    }
}
