<?php
namespace Modules\Inventory\Domain\Entities;
class ProductVariant
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly array $attributes,
        public readonly string $unitPrice,
        public readonly string $costPrice,
        public readonly ?string $barcodeEan13,
        public readonly bool $isActive,
    ) {}
}
