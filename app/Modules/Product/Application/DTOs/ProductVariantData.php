<?php
namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class ProductVariantData extends BaseDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?float $basePrice = null,
        public readonly ?float $costPrice = null,
        public readonly ?string $barcode = null,
        public readonly ?array $attributes = null,
        public readonly bool $isActive = true,
    ) {}
}
