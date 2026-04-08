<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class BatchLot
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly ?string $batchNumber,
        public readonly ?string $lotNumber,
        public readonly ?string $serialNumber,
        public readonly ?string $expiryDate,
        public readonly float $quantity,
        public readonly float $unitCost,
        public readonly string $status,
    ) {}
}
