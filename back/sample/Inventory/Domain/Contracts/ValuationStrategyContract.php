<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Application\DTOs\AllocationResultDTO;
use Modules\Inventory\Application\DTOs\MovementLineDTO;

interface ValuationStrategyContract
{
    public function resolveOutboundUnitCost(MovementLineDTO $line, AllocationResultDTO $allocation): float;

    public function resolveInboundUnitCost(MovementLineDTO $line, ?float $fallbackCost = null): float;
}
