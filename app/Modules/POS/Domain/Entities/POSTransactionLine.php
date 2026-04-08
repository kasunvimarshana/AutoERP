<?php

declare(strict_types=1);

namespace Modules\POS\Domain\Entities;

use DateTimeInterface;

class POSTransactionLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $transactionId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $discountPercent,
        public readonly float $taxRate,
        public readonly float $lineTotal,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
