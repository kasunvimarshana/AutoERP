<?php
namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class ProductData extends BaseDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $sku,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status = 'active',
        public readonly ?int $categoryId = null,
        public readonly ?string $description = null,
        public readonly ?string $barcode = null,
        public readonly ?float $basePrice = null,
        public readonly ?float $costPrice = null,
        public readonly ?int $baseUomId = null,
        public readonly bool $trackInventory = true,
        public readonly bool $trackBatch = false,
        public readonly bool $trackSerial = false,
        public readonly bool $trackLot = false,
        public readonly ?array $attributes = null,
    ) {}
}
