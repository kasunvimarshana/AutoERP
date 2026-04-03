<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\Entities;

class ProductUomSetting
{
    private ?int $id;

    private int $tenantId;

    private int $productId;

    private ?int $baseUomId;

    private ?int $purchaseUomId;

    private ?int $salesUomId;

    private ?int $inventoryUomId;

    private float $purchaseFactor;

    private float $salesFactor;

    private float $inventoryFactor;

    private bool $isActive;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $productId,
        ?int $baseUomId = null,
        ?int $purchaseUomId = null,
        ?int $salesUomId = null,
        ?int $inventoryUomId = null,
        float $purchaseFactor = 1.0,
        float $salesFactor = 1.0,
        float $inventoryFactor = 1.0,
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id              = $id;
        $this->tenantId        = $tenantId;
        $this->productId       = $productId;
        $this->baseUomId       = $baseUomId;
        $this->purchaseUomId   = $purchaseUomId;
        $this->salesUomId      = $salesUomId;
        $this->inventoryUomId  = $inventoryUomId;
        $this->purchaseFactor  = $purchaseFactor;
        $this->salesFactor     = $salesFactor;
        $this->inventoryFactor = $inventoryFactor;
        $this->isActive        = $isActive;
        $this->createdAt       = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt       = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getBaseUomId(): ?int
    {
        return $this->baseUomId;
    }

    public function getPurchaseUomId(): ?int
    {
        return $this->purchaseUomId;
    }

    public function getSalesUomId(): ?int
    {
        return $this->salesUomId;
    }

    public function getInventoryUomId(): ?int
    {
        return $this->inventoryUomId;
    }

    public function getPurchaseFactor(): float
    {
        return $this->purchaseFactor;
    }

    public function getSalesFactor(): float
    {
        return $this->salesFactor;
    }

    public function getInventoryFactor(): float
    {
        return $this->inventoryFactor;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function updateDetails(
        ?int $baseUomId,
        ?int $purchaseUomId,
        ?int $salesUomId,
        ?int $inventoryUomId,
        float $purchaseFactor,
        float $salesFactor,
        float $inventoryFactor,
        bool $isActive
    ): void {
        $this->baseUomId       = $baseUomId;
        $this->purchaseUomId   = $purchaseUomId;
        $this->salesUomId      = $salesUomId;
        $this->inventoryUomId  = $inventoryUomId;
        $this->purchaseFactor  = $purchaseFactor;
        $this->salesFactor     = $salesFactor;
        $this->inventoryFactor = $inventoryFactor;
        $this->isActive        = $isActive;
        $this->updatedAt       = new \DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->isActive  = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
