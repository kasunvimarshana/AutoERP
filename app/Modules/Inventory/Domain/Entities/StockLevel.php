<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use DateTimeInterface;

class StockLevel
{
    public readonly float $availableQuantity;

    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $productId,
        public readonly ?string $variantId,
        public readonly string $warehouseId,
        public readonly ?string $locationId,
        public readonly ?string $batchNumber,
        public readonly ?string $lotNumber,
        public readonly ?string $serialNumber,
        public readonly float $quantity,
        public readonly float $reservedQuantity,
        public readonly ?DateTimeInterface $expiryDate,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {
        $this->availableQuantity = $this->quantity - $this->reservedQuantity;
    }

    public function getAvailableQuantity(): float
    {
        return $this->availableQuantity;
    }

    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate < new \DateTimeImmutable();
    }
}
