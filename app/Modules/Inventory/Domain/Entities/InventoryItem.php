<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class InventoryItem
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly string $warehouseId,
        public readonly ?string $locationId,
        public readonly float $quantityOnHand,
        public readonly float $quantityReserved,
        public readonly float $quantityAvailable,
        public readonly float $averageCost,
    ) {}
}
