<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class InventoryValuationLayer
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $productId,
        private int $warehouseId,
        private float $quantity,
        private float $quantityRemaining,
        private float $unitCost,
        private \DateTimeInterface $receivedAt,
        private ?string $reference,
        private ?int $batchId,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getQuantity(): float { return $this->quantity; }
    public function getQuantityRemaining(): float { return $this->quantityRemaining; }
    public function getUnitCost(): float { return $this->unitCost; }
    public function getReceivedAt(): \DateTimeInterface { return $this->receivedAt; }
    public function getReference(): ?string { return $this->reference; }
    public function getBatchId(): ?int { return $this->batchId; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function hasStock(): bool
    {
        return $this->quantityRemaining > InventoryLevel::FLOAT_TOLERANCE;
    }

    public function consume(float $qty): void
    {
        $this->quantityRemaining = max(0.0, $this->quantityRemaining - $qty);
    }
}
