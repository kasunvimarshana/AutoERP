<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class StockAvailabilityResult
{
    public function __construct(
        public int $itemId,
        public int $warehouseId,
        public string $quantityOnHand,
        public string $quantityReserved,
        public string $quantityAllocated,
        public string $quantityAvailable,
        public ?int $itemVariantId = null,
        public ?int $warehouseLocationId = null,
        public ?int $batchId = null,
    ) {}
}
