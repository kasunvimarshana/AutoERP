<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Entities;

use DateTimeInterface;

class OrderLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $orderType,
        public readonly string $orderId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly ?string $description,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $discount,
        public readonly float $taxRate,
        public readonly float $lineTotal,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function computeLineTotal(): float
    {
        return round(
            $this->quantity
            * $this->unitPrice
            * (1 - $this->discount / 100.0)
            * (1 + $this->taxRate / 100.0),
            2,
        );
    }
}
