<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

final readonly class ProductDTO
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $sku,
        public ?int $categoryId,
        public ?int $brandId,
        public ?int $unitId,
        public string $type,
        public string $costPrice,
        public string $sellingPrice,
        public string $reorderPoint,
        public bool $isActive,
        public ?string $description,
        public ?string $barcode = null,
        public bool $hasVariants = false,
        public ?int $taxRateId = null,
        public ?string $imagePath = null,
    ) {}
}
