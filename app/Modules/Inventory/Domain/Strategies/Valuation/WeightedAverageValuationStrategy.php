<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Strategies\Valuation;

use Modules\Inventory\Application\DTOs\AllocationResultDTO;
use Modules\Inventory\Application\DTOs\MovementLineDTO;
use Modules\Inventory\Domain\Contracts\ValuationStrategyContract;

class WeightedAverageValuationStrategy implements ValuationStrategyContract
{
    public function resolveOutboundUnitCost(MovementLineDTO $line, AllocationResultDTO $allocation): float
    {
        return round((float) ($line->metadata['weighted_average_unit_cost'] ?? $allocation->weightedUnitCost()), 4);
    }

    public function resolveInboundUnitCost(MovementLineDTO $line, ?float $fallbackCost = null): float
    {
        return round($line->providedUnitCost ?? $fallbackCost ?? 0.0, 4);
    }
}
