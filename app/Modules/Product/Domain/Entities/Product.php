<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class Product
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly ?string $categoryId,
        public readonly string $sku,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status,
        public readonly string $unitOfMeasure,
        public readonly float $costPrice,
        public readonly float $sellingPrice,
        public readonly bool $isTrackable,
        public readonly bool $isPurchasable,
        public readonly bool $isSellable,
    ) {}
}
