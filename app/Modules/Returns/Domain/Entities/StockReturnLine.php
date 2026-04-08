<?php

declare(strict_types=1);

namespace Modules\Returns\Domain\Entities;

class StockReturnLine
{
    private ?int $id;
    private int $tenantId;
    private int $stockReturnId;
    private int $productId;
    private ?int $variationId;
    private ?int $batchId;
    private ?int $serialNumberId;
    private ?int $uomId;
    private float $quantityRequested;
    private ?float $quantityApproved;
    private ?float $unitPrice;
    private ?float $unitCost;
    private string $condition;
    private string $disposition;
    private string $qualityCheckStatus;
    private ?int $qualityCheckedBy;
    private ?\DateTimeInterface $qualityCheckedAt;
    private ?string $notes;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $stockReturnId,
        int $productId,
        float $quantityRequested,
        ?int $variationId = null,
        ?int $batchId = null,
        ?int $serialNumberId = null,
        ?int $uomId = null,
        ?float $quantityApproved = null,
        ?float $unitPrice = null,
        ?float $unitCost = null,
        string $condition = 'good',
        string $disposition = 'restock',
        string $qualityCheckStatus = 'pending',
        ?int $qualityCheckedBy = null,
        ?\DateTimeInterface $qualityCheckedAt = null,
        ?string $notes = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id                 = $id;
        $this->tenantId           = $tenantId;
        $this->stockReturnId      = $stockReturnId;
        $this->productId          = $productId;
        $this->variationId        = $variationId;
        $this->batchId            = $batchId;
        $this->serialNumberId     = $serialNumberId;
        $this->uomId              = $uomId;
        $this->quantityRequested  = $quantityRequested;
        $this->quantityApproved   = $quantityApproved;
        $this->unitPrice          = $unitPrice;
        $this->unitCost           = $unitCost;
        $this->condition          = $condition;
        $this->disposition        = $disposition;
        $this->qualityCheckStatus = $qualityCheckStatus;
        $this->qualityCheckedBy   = $qualityCheckedBy;
        $this->qualityCheckedAt   = $qualityCheckedAt;
        $this->notes              = $notes;
        $this->createdAt          = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt          = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getStockReturnId(): int { return $this->stockReturnId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariationId(): ?int { return $this->variationId; }
    public function getBatchId(): ?int { return $this->batchId; }
    public function getSerialNumberId(): ?int { return $this->serialNumberId; }
    public function getUomId(): ?int { return $this->uomId; }
    public function getQuantityRequested(): float { return $this->quantityRequested; }
    public function getQuantityApproved(): ?float { return $this->quantityApproved; }
    public function getUnitPrice(): ?float { return $this->unitPrice; }
    public function getUnitCost(): ?float { return $this->unitCost; }
    public function getCondition(): string { return $this->condition; }
    public function getDisposition(): string { return $this->disposition; }
    public function getQualityCheckStatus(): string { return $this->qualityCheckStatus; }
    public function getQualityCheckedBy(): ?int { return $this->qualityCheckedBy; }
    public function getQualityCheckedAt(): ?\DateTimeInterface { return $this->qualityCheckedAt; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function approve(float $approvedQty): void
    {
        $this->quantityApproved = $approvedQty;
        $this->updatedAt        = new \DateTimeImmutable;
    }

    public function passQualityCheck(int $checkedBy): void
    {
        $this->qualityCheckStatus = 'passed';
        $this->qualityCheckedBy   = $checkedBy;
        $this->qualityCheckedAt   = new \DateTimeImmutable;
        $this->updatedAt          = new \DateTimeImmutable;
    }

    public function failQualityCheck(int $checkedBy): void
    {
        $this->qualityCheckStatus = 'failed';
        $this->qualityCheckedBy   = $checkedBy;
        $this->qualityCheckedAt   = new \DateTimeImmutable;
        $this->updatedAt          = new \DateTimeImmutable;
    }

    public function updateDetails(?string $notes, ?string $condition, ?string $disposition): void
    {
        if ($notes !== null) { $this->notes = $notes; }
        if ($condition !== null) { $this->condition = $condition; }
        if ($disposition !== null) { $this->disposition = $disposition; }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
