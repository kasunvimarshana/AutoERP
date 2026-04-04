<?php
namespace Modules\Product\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class Product extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $sku,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status,
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
    ) {
        parent::__construct($id);
    }
}
