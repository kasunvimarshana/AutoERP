<?php
namespace Modules\Inventory\Domain\Entities;
class StockLevel
{
    public function __construct(
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly string $locationId,
        public readonly string $qty,
        public readonly string $reservedQty,
    ) {
    }
    public function availableQty(): string
    {
        return bcsub($this->qty, $this->reservedQty, 8);
    }
}
