<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use DateTimeInterface;

class StockMovement
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly string $warehouseId,
        public readonly ?string $locationId,
        public readonly string $type,
        public readonly float $quantity,
        public readonly ?string $batchNumber,
        public readonly ?string $lotNumber,
        public readonly ?string $serialNumber,
        public readonly ?string $referenceType,
        public readonly ?string $referenceId,
        public readonly ?string $notes,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
