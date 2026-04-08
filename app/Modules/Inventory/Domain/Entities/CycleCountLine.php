<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use DateTimeInterface;

class CycleCountLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $cycleCountId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly float $systemQty,
        public readonly ?float $countedQty,
        public readonly ?float $variance,
        public readonly ?string $batchNumber,
        public readonly ?string $lotNumber,
        public readonly ?string $serialNumber,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
