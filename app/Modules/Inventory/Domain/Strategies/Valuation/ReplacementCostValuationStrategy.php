<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Valuation;

use Modules\Inventory\Application\DTOs\AllocationResultDTO;
use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Domain\Contracts\ValuationStrategyContract;

class ReplacementCostValuationStrategy implements ValuationStrategyContract
{
    public function resolveOutboundUnitCost(MovementLineDTO $line, AllocationResultDTO $allocation): float
    {
        $replacementCost = $line->metadata['replacement_unit_cost']
            ?? $line->providedUnitCost
            ?? $allocation->weightedUnitCost();

        return round((float) $replacementCost, 4);
    }

    public function resolveInboundUnitCost(MovementLineDTO $line, ?float $fallbackCost = null): float
    {
        $replacementCost = $line->metadata['replacement_unit_cost']
            ?? $line->providedUnitCost
            ?? $fallbackCost
            ?? 0.0;

        return round((float) $replacementCost, 4);
    }
}
