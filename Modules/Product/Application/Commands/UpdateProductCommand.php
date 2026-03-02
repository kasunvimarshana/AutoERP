<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

final readonly class UpdateProductCommand
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $uom,
        public readonly ?string $buyingUom,
        public readonly ?string $sellingUom,
        public readonly string $costingMethod,
        public readonly string $costPrice,
        public readonly string $salePrice,
        public readonly ?string $barcode,
        public readonly string $status,
    ) {}
}
