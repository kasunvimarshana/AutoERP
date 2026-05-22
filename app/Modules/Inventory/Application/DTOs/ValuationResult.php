<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

final class ValuationResult
{
    /**
     * @param array<int, \Modules\Inventory\Application\DTOs\ValuationLayerConsumption> $consumptions
     */
    public function __construct(
        public string $valuationMethod,
        public string $direction,
        public float $quantity,
        public float $unitCost,
        public float $totalCost,
        public array $consumptions = [],
        public float $balanceQuantity = 0.0,
        public float $balanceValue = 0.0,
    ) {
    }
}
