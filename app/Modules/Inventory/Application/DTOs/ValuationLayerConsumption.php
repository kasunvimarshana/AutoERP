<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

final readonly class ValuationLayerConsumption
{
    public function __construct(
        public int $layerId,
        public float $consumedQuantity,
        public float $unitCost,
    ) {
    }
}
