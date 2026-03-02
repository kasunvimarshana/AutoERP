<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

final class InventoryLot
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly ?string $lotNumber,
        public readonly ?string $serialNumber,
        public readonly ?string $batchNumber,
        public readonly ?string $manufacturedDate,
        public readonly ?string $expiryDate,
        public readonly string $quantity,
        public readonly ?string $notes,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
