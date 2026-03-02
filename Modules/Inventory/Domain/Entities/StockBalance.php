<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

final class StockBalance
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly int $productId,
        public readonly string $quantityOnHand,
        public readonly string $quantityReserved,
        public readonly string $averageCost,
        public readonly ?string $updatedAt,
    ) {
        $this->validateNonNegative($quantityOnHand, 'quantity_on_hand');
        $this->validateNonNegative($quantityReserved, 'quantity_reserved');
    }

    public function availableQuantity(): string
    {
        return bcsub($this->quantityOnHand, $this->quantityReserved, 4);
    }

    private function validateNonNegative(string $value, string $field): void
    {
        if (bccomp($value, '0', 4) < 0) {
            throw new \DomainException("Field '{$field}' cannot be negative.");
        }
    }
}
