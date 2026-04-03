<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class InventorySerialNumber
{
    private ?int $id;
    private int $tenantId;
    private int $productId;
    private ?int $variationId;
    private ?int $batchId;
    private string $serialNumber;
    private ?int $locationId;
    private string $status;
    private ?float $purchasePrice;
    private string $currency;
    private ?\DateTimeInterface $purchasedAt;
    private ?\DateTimeInterface $soldAt;
    private ?\DateTimeInterface $returnedAt;
    private ?string $notes;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $productId,
        string $serialNumber,
        ?int $variationId = null,
        ?int $batchId = null,
        ?int $locationId = null,
        string $status = 'available',
        ?float $purchasePrice = null,
        string $currency = 'USD',
        ?\DateTimeInterface $purchasedAt = null,
        ?\DateTimeInterface $soldAt = null,
        ?\DateTimeInterface $returnedAt = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id            = $id;
        $this->tenantId      = $tenantId;
        $this->productId     = $productId;
        $this->variationId   = $variationId;
        $this->batchId       = $batchId;
        $this->serialNumber  = $serialNumber;
        $this->locationId    = $locationId;
        $this->status        = $status;
        $this->purchasePrice = $purchasePrice;
        $this->currency      = $currency;
        $this->purchasedAt   = $purchasedAt;
        $this->soldAt        = $soldAt;
        $this->returnedAt    = $returnedAt;
        $this->notes         = $notes;
        $this->metadata      = $metadata ?? new Metadata([]);
        $this->createdAt     = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt     = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariationId(): ?int { return $this->variationId; }
    public function getBatchId(): ?int { return $this->batchId; }
    public function getSerialNumber(): string { return $this->serialNumber; }
    public function getLocationId(): ?int { return $this->locationId; }
    public function getStatus(): string { return $this->status; }
    public function getPurchasePrice(): ?float { return $this->purchasePrice; }
    public function getCurrency(): string { return $this->currency; }
    public function getPurchasedAt(): ?\DateTimeInterface { return $this->purchasedAt; }
    public function getSoldAt(): ?\DateTimeInterface { return $this->soldAt; }
    public function getReturnedAt(): ?\DateTimeInterface { return $this->returnedAt; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function updateDetails(
        ?int $batchId,
        ?int $locationId,
        string $status,
        ?float $purchasePrice,
        string $currency,
        ?\DateTimeInterface $purchasedAt,
        ?string $notes,
        ?Metadata $metadata
    ): void {
        $this->batchId       = $batchId;
        $this->locationId    = $locationId;
        $this->status        = $status;
        $this->purchasePrice = $purchasePrice;
        $this->currency      = $currency;
        $this->purchasedAt   = $purchasedAt;
        $this->notes         = $notes;
        $this->metadata      = $metadata ?? new Metadata([]);
        $this->updatedAt     = new \DateTimeImmutable;
    }

    public function markSold(?\DateTimeInterface $soldAt = null): void
    {
        $this->status    = 'sold';
        $this->soldAt    = $soldAt ?? new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markReturned(?\DateTimeInterface $returnedAt = null): void
    {
        $this->status     = 'returned';
        $this->returnedAt = $returnedAt ?? new \DateTimeImmutable;
        $this->updatedAt  = new \DateTimeImmutable;
    }

    public function markDamaged(): void
    {
        $this->status    = 'damaged';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markScrapped(): void
    {
        $this->status    = 'scrapped';
        $this->updatedAt = new \DateTimeImmutable;
    }
}
