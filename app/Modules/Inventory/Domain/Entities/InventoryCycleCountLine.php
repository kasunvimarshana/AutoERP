<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

class InventoryCycleCountLine
{
    private ?int $id;
    private int $tenantId;
    private int $cycleCountId;
    private int $productId;
    private ?int $variationId;
    private ?int $batchId;
    private ?int $serialNumberId;
    private ?int $locationId;
    private float $expectedQty;
    private ?float $countedQty;
    private float $varianceQty;
    private string $status;
    private ?\DateTimeInterface $countedAt;
    private ?int $countedBy;
    private ?string $notes;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $cycleCountId,
        int $productId,
        float $expectedQty = 0.0,
        ?int $variationId = null,
        ?int $batchId = null,
        ?int $serialNumberId = null,
        ?int $locationId = null,
        ?float $countedQty = null,
        float $varianceQty = 0.0,
        string $status = 'pending',
        ?\DateTimeInterface $countedAt = null,
        ?int $countedBy = null,
        ?string $notes = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id             = $id;
        $this->tenantId       = $tenantId;
        $this->cycleCountId   = $cycleCountId;
        $this->productId      = $productId;
        $this->variationId    = $variationId;
        $this->batchId        = $batchId;
        $this->serialNumberId = $serialNumberId;
        $this->locationId     = $locationId;
        $this->expectedQty    = $expectedQty;
        $this->countedQty     = $countedQty;
        $this->varianceQty    = $varianceQty;
        $this->status         = $status;
        $this->countedAt      = $countedAt;
        $this->countedBy      = $countedBy;
        $this->notes          = $notes;
        $this->createdAt      = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt      = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getCycleCountId(): int { return $this->cycleCountId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariationId(): ?int { return $this->variationId; }
    public function getBatchId(): ?int { return $this->batchId; }
    public function getSerialNumberId(): ?int { return $this->serialNumberId; }
    public function getLocationId(): ?int { return $this->locationId; }
    public function getExpectedQty(): float { return $this->expectedQty; }
    public function getCountedQty(): ?float { return $this->countedQty; }
    public function getVarianceQty(): float { return $this->varianceQty; }
    public function getStatus(): string { return $this->status; }
    public function getCountedAt(): ?\DateTimeInterface { return $this->countedAt; }
    public function getCountedBy(): ?int { return $this->countedBy; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function recordCount(float $countedQty, ?int $countedBy = null): void
    {
        $this->countedQty  = $countedQty;
        $this->varianceQty = $countedQty - $this->expectedQty;
        $this->status      = 'counted';
        $this->countedAt   = new \DateTimeImmutable;
        $this->countedBy   = $countedBy;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function approve(): void
    {
        $this->status    = 'approved';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function reject(): void
    {
        $this->status    = 'rejected';
        $this->updatedAt = new \DateTimeImmutable;
    }
}
