<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use DateTimeImmutable;
use Modules\Inventory\Domain\Enums\LedgerEntryType;

class StockLedgerEntry
{
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private readonly int $productId,
        private readonly ?int $variantId,
        private readonly int $warehouseId,
        private readonly LedgerEntryType $type,
        private readonly string $quantity,
        private readonly string $unitCost,
        private readonly ?string $referenceType,
        private readonly ?int $referenceId,
        private readonly ?string $notes,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariantId(): ?int { return $this->variantId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getType(): LedgerEntryType { return $this->type; }
    public function getQuantity(): string { return $this->quantity; }
    public function getUnitCost(): string { return $this->unitCost; }
    public function getReferenceType(): ?string { return $this->referenceType; }
    public function getReferenceId(): ?int { return $this->referenceId; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }

    public function isInbound(): bool
    {
        return $this->type->isInbound();
    }

    /**
     * Returns the signed quantity: positive for inbound, negative for outbound.
     */
    public function signedQuantity(): string
    {
        return $this->isInbound()
            ? bcadd($this->quantity, '0', 4)
            : bcsub('0', $this->quantity, 4);
    }
}
