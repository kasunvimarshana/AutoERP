<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Allocation;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\MovementLineDTO;

class RuleBasedAllocationStrategy extends FifoAllocationStrategy
{
    protected function orderedLayers(Collection $layers, MovementLineDTO $line): Collection
    {
        $rules = $line->metadata['allocation_rules'] ?? [];
        if (!is_array($rules) || $rules === []) {
            return parent::orderedLayers($layers, $line);
        }

        // Simple stable sort based on optional rank rules per dimension.
        return $layers
            ->sortBy(function ($layer) use ($rules): array {
                $batchRank = isset($rules['batch'][$layer->batch_id]) ? (int) $rules['batch'][$layer->batch_id] : 999999;
                $locationRank = isset($rules['location'][$layer->location_id]) ? (int) $rules['location'][$layer->location_id] : 999999;

                return [$batchRank, $locationRank, (string) ($layer->layer_date ?? ''), (int) $layer->id];
            })
            ->values();
    }
}
