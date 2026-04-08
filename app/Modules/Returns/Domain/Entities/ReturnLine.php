<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Entities;

use DateTimeInterface;

class ReturnLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $returnType,
        public readonly string $returnId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $lineTotal,
        public readonly ?string $batchNumber,
        public readonly ?string $lotNumber,
        public readonly ?string $serialNumber,
        public readonly string $condition,
        public readonly bool $restockable,
        public readonly ?string $qualityNotes,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
