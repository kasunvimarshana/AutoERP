<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class ValuationResultData
{
    public function __construct(
        public string $quantity,
        public string $unitCost,
        public string $totalCost,
    ) {}
}
