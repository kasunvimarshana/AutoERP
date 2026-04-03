<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Modules\Inventory\Domain\Exceptions\InsufficientStockException;

class InventoryLevel
{
    private ?int $id;
    private int $tenantId;
    private int $productId;
    private ?int $variationId;
    private ?int $locationId;
    private ?int $batchId;
    private ?int $uomId;
    private float $qtyOnHand;
    private float $qtyReserved;
    private float $qtyAvailable;
    private float $qtyOnOrder;
    private ?float $reorderPoint;
    private ?float $reorderQty;
    private ?float $maxQty;
    private ?float $minQty;
    private ?\DateTimeInterface $lastCountedAt;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $productId,
        ?int $variationId = null,
        ?int $locationId = null,
        ?int $batchId = null,
        ?int $uomId = null,
        float $qtyOnHand = 0.0,
        float $qtyReserved = 0.0,
        ?float $qtyAvailable = null,
        float $qtyOnOrder = 0.0,
        ?float $reorderPoint = null,
        ?float $reorderQty = null,
        ?float $maxQty = null,
        ?float $minQty = null,
        ?\DateTimeInterface $lastCountedAt = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id            = $id;
        $this->tenantId      = $tenantId;
        $this->productId     = $productId;
        $this->variationId   = $variationId;
        $this->locationId    = $locationId;
        $this->batchId       = $batchId;
        $this->uomId         = $uomId;
        $this->qtyOnHand     = $qtyOnHand;
        $this->qtyReserved   = $qtyReserved;
        $this->qtyAvailable  = $qtyAvailable ?? ($qtyOnHand - $qtyReserved);
        $this->qtyOnOrder    = $qtyOnOrder;
        $this->reorderPoint  = $reorderPoint;
        $this->reorderQty    = $reorderQty;
        $this->maxQty        = $maxQty;
        $this->minQty        = $minQty;
        $this->lastCountedAt = $lastCountedAt;
        $this->createdAt     = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt     = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariationId(): ?int { return $this->variationId; }
    public function getLocationId(): ?int { return $this->locationId; }
    public function getBatchId(): ?int { return $this->batchId; }
    public function getUomId(): ?int { return $this->uomId; }
    public function getQtyOnHand(): float { return $this->qtyOnHand; }
    public function getQtyReserved(): float { return $this->qtyReserved; }
    public function getQtyAvailable(): float { return $this->qtyAvailable; }
    public function getQtyOnOrder(): float { return $this->qtyOnOrder; }
    public function getReorderPoint(): ?float { return $this->reorderPoint; }
    public function getReorderQty(): ?float { return $this->reorderQty; }
    public function getMaxQty(): ?float { return $this->maxQty; }
    public function getMinQty(): ?float { return $this->minQty; }
    public function getLastCountedAt(): ?\DateTimeInterface { return $this->lastCountedAt; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function updateQuantities(float $qtyOnHand, float $qtyReserved, float $qtyOnOrder): void
    {
        $this->qtyOnHand    = $qtyOnHand;
        $this->qtyReserved  = $qtyReserved;
        $this->qtyOnOrder   = $qtyOnOrder;
        $this->qtyAvailable = $qtyOnHand - $qtyReserved;
        $this->updatedAt    = new \DateTimeImmutable;
    }

    public function addStock(float $qty): void
    {
        $this->qtyOnHand    += $qty;
        $this->qtyAvailable  = $this->qtyOnHand - $this->qtyReserved;
        $this->updatedAt     = new \DateTimeImmutable;
    }

    public function removeStock(float $qty, bool $allowNegative = false): void
    {
        if (! $allowNegative && $this->qtyAvailable < $qty) {
            throw new InsufficientStockException($this->productId, $this->qtyAvailable, $qty);
        }
        $this->qtyOnHand    -= $qty;
        $this->qtyAvailable  = $this->qtyOnHand - $this->qtyReserved;
        $this->updatedAt     = new \DateTimeImmutable;
    }

    public function reserve(float $qty): void
    {
        $this->qtyReserved  += $qty;
        $this->qtyAvailable  = $this->qtyOnHand - $this->qtyReserved;
        $this->updatedAt     = new \DateTimeImmutable;
    }

    public function release(float $qty): void
    {
        $this->qtyReserved  = max(0.0, $this->qtyReserved - $qty);
        $this->qtyAvailable = $this->qtyOnHand - $this->qtyReserved;
        $this->updatedAt    = new \DateTimeImmutable;
    }

    public function isLowStock(): bool
    {
        if ($this->reorderPoint === null) {
            return false;
        }

        return $this->qtyOnHand <= $this->reorderPoint;
    }
}
