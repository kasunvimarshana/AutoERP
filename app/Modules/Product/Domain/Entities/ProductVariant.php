<?php
namespace Modules\Product\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class ProductVariant extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $productId,
        public readonly string $sku,
        public readonly string $name,
        public readonly ?float $basePrice = null,
        public readonly ?float $costPrice = null,
        public readonly ?string $barcode = null,
        public readonly ?array $attributes = null,
        public readonly bool $isActive = true,
    ) {
        parent::__construct($id);
    }
}
