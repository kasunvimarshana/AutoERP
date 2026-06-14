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
        public string $quantityInTransit = '0.000000',
        public string $quantityReturned = '0.000000',
        public string $quantityDamaged = '0.000000',
        public string $quantityQuarantine = '0.000000',
        public string $quantityExpired = '0.000000',
        public string $quantityScrapped = '0.000000',
        public string $quantityTotal = '0.000000',
        public string $quantityBasis = 'base',
        public ?int $baseUomId = null,
        public ?int $itemVariantId = null,
        public ?int $warehouseLocationId = null,
        public ?int $batchId = null,
    ) {}
}
