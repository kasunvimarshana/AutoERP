<?php
namespace Modules\Pricing\Domain\Entities;
use Modules\Core\Domain\Entities\BaseEntity;

class PriceListItem extends BaseEntity
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $priceListId,
        public readonly int $productId,
        public readonly float $price,
        public readonly ?int $variantId = null,
        public readonly ?float $minQty = null,
        public readonly ?float $maxQty = null,
        public readonly ?float $discountPercent = null,
        public readonly string $uom = 'unit',
    ) { parent::__construct($id); }
}
