<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

final class ProductVariant
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly string $sku,
        public readonly array $attributeValues,
        public readonly bool $isActive,
        public readonly ?string $barcode = null,
        public readonly ?string $name = null,
        public readonly ?float $costPrice = null,
        public readonly ?float $sellingPrice = null,
        public readonly ?float $weight = null,
        public readonly ?array $images = null,
        public readonly ?array $metadata = null,
    ) {}

    public function getEffectiveSellingPrice(float $parentSellingPrice): float
    {
        return $this->sellingPrice ?? $parentSellingPrice;
    }

    public function getEffectiveCostPrice(float $parentCostPrice): float
    {
        return $this->costPrice ?? $parentCostPrice;
    }
}
