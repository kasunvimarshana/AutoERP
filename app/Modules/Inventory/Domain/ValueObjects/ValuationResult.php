<?php

namespace Modules\Inventory\Domain\ValueObjects;

class ValuationResult
{
    public function __construct(
        public readonly float $quantity,
        public readonly float $unitCost,
        public readonly float $totalCost,
        public readonly float $balanceQuantity,
        public readonly float $balanceValue,
    ) {}
}
