<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Entities;

final class PurchaseOrderLine
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $purchaseOrderId,
        public readonly int $productId,
        public readonly ?string $description,
        public readonly string $quantityOrdered,
        public readonly string $quantityReceived,
        public readonly string $unitCost,
        public readonly string $taxRate,
        public readonly string $discountRate,
        public readonly string $lineTotal,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public function remainingQuantity(): string
    {
        return bcsub($this->quantityOrdered, $this->quantityReceived, 4);
    }

    public function isFullyReceived(): bool
    {
        return bccomp($this->quantityReceived, $this->quantityOrdered, 4) >= 0;
    }
}
