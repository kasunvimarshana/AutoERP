<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class InventoryBatch
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $productId,
        private int $warehouseId,
        private string $batchNumber,
        private ?string $lotNumber,
        private ?string $serialNumber,
        private float $quantity,
        private float $quantityRemaining,
        private float $costPrice,
        private ?\DateTimeInterface $manufacturedAt,
        private ?\DateTimeInterface $expiresAt,
        private \DateTimeInterface $receivedAt,
        private string $status,   // active|exhausted|quarantine|expired
        private ?string $reference,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getBatchNumber(): string { return $this->batchNumber; }
    public function getLotNumber(): ?string { return $this->lotNumber; }
    public function getSerialNumber(): ?string { return $this->serialNumber; }
    public function getQuantity(): float { return $this->quantity; }
    public function getQuantityRemaining(): float { return $this->quantityRemaining; }
    public function getCostPrice(): float { return $this->costPrice; }
    public function getManufacturedAt(): ?\DateTimeInterface { return $this->manufacturedAt; }
    public function getExpiresAt(): ?\DateTimeInterface { return $this->expiresAt; }
    public function getReceivedAt(): \DateTimeInterface { return $this->receivedAt; }
    public function getStatus(): string { return $this->status; }
    public function getReference(): ?string { return $this->reference; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isActive(): bool { return $this->status === 'active'; }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable());
    }

    public function consume(float $qty): void
    {
        if ($qty > $this->quantityRemaining + InventoryLevel::FLOAT_TOLERANCE) {
            throw new \DomainException("Cannot consume {$qty} from batch, only {$this->quantityRemaining} remaining.");
        }
        $this->quantityRemaining = max(0.0, $this->quantityRemaining - $qty);
        if ($this->quantityRemaining < InventoryLevel::FLOAT_TOLERANCE) {
            $this->status = 'exhausted';
        }
    }

    public function hasStock(): bool
    {
        return $this->quantityRemaining > InventoryLevel::FLOAT_TOLERANCE;
    }
}
