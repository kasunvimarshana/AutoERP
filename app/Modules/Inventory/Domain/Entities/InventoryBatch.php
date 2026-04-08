<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class InventoryBatch
{
    private ?int $id;
    private int $tenantId;
    private int $productId;
    private ?int $variationId;
    private string $batchNumber;
    private ?string $lotNumber;
    private ?\DateTimeInterface $manufactureDate;
    private ?\DateTimeInterface $expiryDate;
    private ?\DateTimeInterface $bestBeforeDate;
    private ?int $supplierId;
    private ?string $supplierBatchRef;
    private float $initialQty;
    private float $remainingQty;
    private float $unitCost;
    private string $currency;
    private string $status;
    private ?string $notes;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $productId,
        string $batchNumber,
        ?int $variationId = null,
        ?string $lotNumber = null,
        ?\DateTimeInterface $manufactureDate = null,
        ?\DateTimeInterface $expiryDate = null,
        ?\DateTimeInterface $bestBeforeDate = null,
        ?int $supplierId = null,
        ?string $supplierBatchRef = null,
        float $initialQty = 0.0,
        float $remainingQty = 0.0,
        float $unitCost = 0.0,
        string $currency = 'USD',
        string $status = 'active',
        ?string $notes = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id               = $id;
        $this->tenantId         = $tenantId;
        $this->productId        = $productId;
        $this->variationId      = $variationId;
        $this->batchNumber      = $batchNumber;
        $this->lotNumber        = $lotNumber;
        $this->manufactureDate  = $manufactureDate;
        $this->expiryDate       = $expiryDate;
        $this->bestBeforeDate   = $bestBeforeDate;
        $this->supplierId       = $supplierId;
        $this->supplierBatchRef = $supplierBatchRef;
        $this->initialQty       = $initialQty;
        $this->remainingQty     = $remainingQty;
        $this->unitCost         = $unitCost;
        $this->currency         = $currency;
        $this->status           = $status;
        $this->notes            = $notes;
        $this->metadata         = $metadata ?? new Metadata([]);
        $this->createdAt        = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt        = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariationId(): ?int { return $this->variationId; }
    public function getBatchNumber(): string { return $this->batchNumber; }
    public function getLotNumber(): ?string { return $this->lotNumber; }
    public function getManufactureDate(): ?\DateTimeInterface { return $this->manufactureDate; }
    public function getExpiryDate(): ?\DateTimeInterface { return $this->expiryDate; }
    public function getBestBeforeDate(): ?\DateTimeInterface { return $this->bestBeforeDate; }
    public function getSupplierId(): ?int { return $this->supplierId; }
    public function getSupplierBatchRef(): ?string { return $this->supplierBatchRef; }
    public function getInitialQty(): float { return $this->initialQty; }
    public function getRemainingQty(): float { return $this->remainingQty; }
    public function getUnitCost(): float { return $this->unitCost; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): string { return $this->status; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function updateDetails(
        string $batchNumber,
        ?string $lotNumber,
        ?\DateTimeInterface $manufactureDate,
        ?\DateTimeInterface $expiryDate,
        ?\DateTimeInterface $bestBeforeDate,
        ?int $supplierId,
        ?string $supplierBatchRef,
        float $initialQty,
        float $unitCost,
        string $currency,
        string $status,
        ?string $notes,
        ?Metadata $metadata
    ): void {
        $this->batchNumber      = $batchNumber;
        $this->lotNumber        = $lotNumber;
        $this->manufactureDate  = $manufactureDate;
        $this->expiryDate       = $expiryDate;
        $this->bestBeforeDate   = $bestBeforeDate;
        $this->supplierId       = $supplierId;
        $this->supplierBatchRef = $supplierBatchRef;
        $this->initialQty       = $initialQty;
        $this->unitCost         = $unitCost;
        $this->currency         = $currency;
        $this->status           = $status;
        $this->notes            = $notes;
        $this->metadata         = $metadata ?? new Metadata([]);
        $this->updatedAt        = new \DateTimeImmutable;
    }

    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate < new \DateTimeImmutable;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function deplete(): void
    {
        $this->status       = 'depleted';
        $this->remainingQty = 0.0;
        $this->updatedAt    = new \DateTimeImmutable;
    }

    public function quarantine(): void
    {
        $this->status    = 'quarantine';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function consume(float $qty): void
    {
        $this->remainingQty = max(0.0, $this->remainingQty - $qty);
        if ($this->remainingQty <= 0.0) {
            $this->status = 'depleted';
        }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
