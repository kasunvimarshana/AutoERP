<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Inventory\Domain\Support\Decimal;
use Modules\Inventory\Domain\ValueObjects\AllocationDecision;

final readonly class AllocationResultDTO
{
    /** @param array<int, AllocationDecision> $decisions */
    public function __construct(
        public array $decisions,
        public float $requestedQuantity,
        public float $allocatedQuantity,
    ) {
    }

    public function remainingQuantity(): float
    {
        return Decimal::sub($this->requestedQuantity, $this->allocatedQuantity);
    }

    public function totalCost(): float
    {
        $total = 0.0;

        foreach ($this->decisions as $decision) {
            $total = Decimal::add($total, $decision->totalCost());
        }

        return $total;
    }

    public function weightedUnitCost(): float
    {
        if ($this->allocatedQuantity <= 0.0) {
            return 0.0;
        }

        return Decimal::div($this->totalCost(), $this->allocatedQuantity);
    }
}
