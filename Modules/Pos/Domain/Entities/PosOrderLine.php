<?php

declare(strict_types=1);

namespace Modules\Pos\Domain\Entities;

final class PosOrderLine
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $posOrderId,
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $sku,
        public readonly string $quantity,
        public readonly string $unitPrice,
        public readonly string $discountAmount,
        public readonly string $taxAmount,
        public readonly string $lineTotal,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
