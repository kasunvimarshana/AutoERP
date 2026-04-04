<?php
namespace Modules\Pricing\Application\DTOs;
use Modules\Core\Application\DTOs\BaseDTO;

class PriceListItemData extends BaseDTO
{
    public function __construct(
        public readonly int $priceListId,
        public readonly int $productId,
        public readonly float $price,
        public readonly ?int $variantId = null,
        public readonly ?float $minQty = null,
        public readonly ?float $maxQty = null,
        public readonly ?float $discountPercent = null,
        public readonly string $uom = 'unit',
    ) {}
}
