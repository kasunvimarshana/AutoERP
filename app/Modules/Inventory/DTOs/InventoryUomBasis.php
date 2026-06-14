<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class InventoryUomBasis
{
    public function __construct(
        public string $enteredQuantity,
        public string $enteredUnitCost,
        public ?int $enteredUomId,
        public string $baseQuantity,
        public string $baseUnitCost,
        public string $conversionFactor,
        public ?int $baseUomId,
    ) {}
}
