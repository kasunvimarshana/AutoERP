<?php
namespace Modules\Sales\Domain\Entities;

class PriceListItem
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $priceListId,
        public readonly string  $tenantId,
        public readonly string  $productId,
        public readonly ?string $variantId,
        public readonly string  $strategy,
        public readonly string  $amount,
        public readonly string  $minQty,
        public readonly ?string $uom,
    ) {}
}
