<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

final readonly class UpdateProductCommand
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public string $type,
        public string $costPrice,
        public string $sellingPrice,
        public string $reorderPoint = '0',
        public ?int $categoryId = null,
        public ?int $brandId = null,
        public ?int $unitId = null,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}
}
