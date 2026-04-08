<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class InventoryMovement
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly string $warehouseId,
        public readonly string $type,
        public readonly float $quantity,
        public readonly float $unitCost,
        public readonly float $totalCost,
    ) {}
}
