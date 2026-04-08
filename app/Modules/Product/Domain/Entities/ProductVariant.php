<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use DateTimeInterface;

class ProductVariant
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $productId,
        public readonly string $name,
        public readonly string $sku,
        public readonly ?string $barcode,
        public readonly array $attributes,
        public readonly float $costPrice,
        public readonly float $salePrice,
        public readonly float $stockQuantity,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
