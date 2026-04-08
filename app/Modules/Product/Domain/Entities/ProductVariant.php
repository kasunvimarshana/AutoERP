<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductVariant
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?array $attributes,
        public readonly float $costPrice,
        public readonly float $sellingPrice,
        public readonly bool $isActive,
    ) {}
}
