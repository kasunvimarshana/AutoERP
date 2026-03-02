<?php

declare(strict_types=1);

namespace Modules\Product\Application\Commands;

final readonly class CreateProductCommand
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $type,
        public readonly string $uom,
        public readonly ?string $buyingUom = null,
        public readonly ?string $sellingUom = null,
        public readonly string $costingMethod = 'fifo',
        public readonly string $costPrice = '0.0000',
        public readonly string $salePrice = '0.0000',
        public readonly ?string $barcode = null,
        public readonly string $status = 'active',
    ) {}
}
