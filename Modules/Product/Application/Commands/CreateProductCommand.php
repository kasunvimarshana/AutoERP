<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

final readonly class CreateProductCommand
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $sku,
        public string $type,
        public string $costPrice,
        public string $sellingPrice,
        public string $reorderPoint = '0',
        public ?int $categoryId = null,
        public ?int $brandId = null,
        public ?int $unitId = null,
        public ?string $description = null,
        public ?string $barcode = null,
        public bool $hasVariants = false,
        public ?int $taxRateId = null,
        public bool $isActive = true,
    ) {}
}
