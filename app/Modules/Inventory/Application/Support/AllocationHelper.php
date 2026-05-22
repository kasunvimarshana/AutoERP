<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Support;

use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationLine;

final class AllocationHelper
{
    /**
     * @return AllocationLine[]
     */
    public static function greedyAllocate(Collection $candidates, float $requiredQuantity): array
    {
        $remaining = max(0.0, $requiredQuantity);
        $lines = [];

        foreach ($candidates as $candidate) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) ($candidate->available_quantity ?? 0);
            if ($available <= 0) {
                continue;
            }

            $allocated = min($available, $remaining);
            $remaining -= $allocated;

            $lines[] = new AllocationLine(
                stockLevelId: (int) $candidate->stock_level_id,
                locationId: (int) $candidate->location_id,
                batchId: isset($candidate->batch_id) ? (int) $candidate->batch_id : null,
                serialId: isset($candidate->serial_id) ? (int) $candidate->serial_id : null,
                quantity: $allocated,
                unitCost: isset($candidate->unit_cost) ? (float) $candidate->unit_cost : null,
                batchNumber: $candidate->batch_number ?? null,
                lotNumber: $candidate->lot_number ?? null,
            );
        }

        return $lines;
    }
}
