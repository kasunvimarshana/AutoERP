<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class InventoryLevel
{
    public const FLOAT_TOLERANCE = 0.0001;

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $productId,
        private int $warehouseId,
        private ?int $locationId,
        private float $quantityOnHand,
        private float $quantityReserved,
        private float $quantityInTransit,
        private string $valuationMethod,  // fifo|lifo|average|specific
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getLocationId(): ?int { return $this->locationId; }
    public function getQuantityOnHand(): float { return $this->quantityOnHand; }
    public function getQuantityReserved(): float { return $this->quantityReserved; }
    public function getQuantityInTransit(): float { return $this->quantityInTransit; }
    public function getAvailableQuantity(): float { return $this->quantityOnHand - $this->quantityReserved; }
    public function getValuationMethod(): string { return $this->valuationMethod; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function receive(float $quantity): void
    {
        if ($quantity <= 0) throw new \InvalidArgumentException("Receive quantity must be positive.");
        $this->quantityOnHand += $quantity;
    }

    public function issue(float $quantity): void
    {
        if ($quantity <= 0) throw new \InvalidArgumentException("Issue quantity must be positive.");
        if ($this->getAvailableQuantity() < $quantity - self::FLOAT_TOLERANCE) {
            throw new \DomainException("Insufficient stock. Available: {$this->getAvailableQuantity()}, Requested: {$quantity}");
        }
        $this->quantityOnHand -= $quantity;
    }

    public function reserve(float $quantity): void
    {
        if ($quantity <= 0) throw new \InvalidArgumentException("Reserve quantity must be positive.");
        if ($this->getAvailableQuantity() < $quantity - self::FLOAT_TOLERANCE) {
            throw new \DomainException("Insufficient stock to reserve. Available: {$this->getAvailableQuantity()}, Requested: {$quantity}");
        }
        $this->quantityReserved += $quantity;
    }

    public function releaseReservation(float $quantity): void
    {
        if ($quantity <= 0) throw new \InvalidArgumentException("Release quantity must be positive.");
        $this->quantityReserved = max(0.0, $this->quantityReserved - $quantity);
    }

    public function adjust(float $newQuantity): float
    {
        $diff = $newQuantity - $this->quantityOnHand;
        $this->quantityOnHand = $newQuantity;
        return $diff;
    }
}
