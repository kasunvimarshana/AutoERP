<?php
namespace Modules\Inventory\Domain\Entities;
class Product
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $type,
        public readonly string $sku,
        public readonly ?string $categoryId,
        public readonly string $unitPrice,
        public readonly string $costPrice,
        public readonly string $purchaseUom,
        public readonly string $saleUom,
        public readonly string $inventoryUom,
        public readonly string $status,
        public readonly ?string $barcodeEan13,
        public readonly bool $trackLots,
        public readonly bool $trackSerials,
    ) {}
}
