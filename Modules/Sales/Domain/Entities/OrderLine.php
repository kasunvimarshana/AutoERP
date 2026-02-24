<?php
namespace Modules\Sales\Domain\Entities;
class OrderLine
{
    public function __construct(
        public readonly ?string $productId,
        public readonly string $description,
        public readonly string $qty,
        public readonly string $unitPrice,
        public readonly string $discount,
        public readonly string $taxAmount,
        public readonly string $lineTotal,
        public readonly string $uom,
    ) {}
}
