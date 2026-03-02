<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

final class SalesOrderLine
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $salesOrderId,
        public readonly int $productId,
        public readonly ?string $description,
        public readonly string $quantity,
        public readonly string $unitPrice,
        public readonly string $taxRate,
        public readonly string $discountRate,
        public readonly string $lineTotal,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
