<?php

declare(strict_types=1);

namespace Modules\StockMovement\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class StockMovement
{
    private ?int $id;
    private int $tenantId;
    private string $referenceNumber;
    private string $movementType;
    private string $status;
    private int $productId;
    private ?int $variationId;
    private ?int $fromLocationId;
    private ?int $toLocationId;
    private ?int $batchId;
    private ?int $serialNumberId;
    private ?int $uomId;
    private float $quantity;
    private ?float $unitCost;
    private string $currency;
    private ?string $referenceType;
    private ?int $referenceId;
    private ?int $performedBy;
    private ?\DateTimeInterface $movementDate;
    private ?string $notes;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $referenceNumber,
        string $movementType,
        int $productId,
        float $quantity,
        ?int $variationId = null,
        ?int $fromLocationId = null,
        ?int $toLocationId = null,
        ?int $batchId = null,
        ?int $serialNumberId = null,
        ?int $uomId = null,
        ?float $unitCost = null,
        string $currency = 'USD',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $performedBy = null,
        ?\DateTimeInterface $movementDate = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        string $status = 'draft',
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id             = $id;
        $this->tenantId       = $tenantId;
        $this->referenceNumber = $referenceNumber;
        $this->movementType   = $movementType;
        $this->status         = $status;
        $this->productId      = $productId;
        $this->variationId    = $variationId;
        $this->fromLocationId = $fromLocationId;
        $this->toLocationId   = $toLocationId;
        $this->batchId        = $batchId;
        $this->serialNumberId = $serialNumberId;
        $this->uomId          = $uomId;
        $this->quantity       = $quantity;
        $this->unitCost       = $unitCost;
        $this->currency       = $currency;
        $this->referenceType  = $referenceType;
        $this->referenceId    = $referenceId;
        $this->performedBy    = $performedBy;
        $this->movementDate   = $movementDate;
        $this->notes          = $notes;
        $this->metadata       = $metadata ?? new Metadata([]);
        $this->createdAt      = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt      = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function getMovementType(): string { return $this->movementType; }
    public function getStatus(): string { return $this->status; }
    public function getProductId(): int { return $this->productId; }
    public function getVariationId(): ?int { return $this->variationId; }
    public function getFromLocationId(): ?int { return $this->fromLocationId; }
    public function getToLocationId(): ?int { return $this->toLocationId; }
    public function getBatchId(): ?int { return $this->batchId; }
    public function getSerialNumberId(): ?int { return $this->serialNumberId; }
    public function getUomId(): ?int { return $this->uomId; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitCost(): ?float { return $this->unitCost; }
    public function getCurrency(): string { return $this->currency; }
    public function getReferenceType(): ?string { return $this->referenceType; }
    public function getReferenceId(): ?int { return $this->referenceId; }
    public function getPerformedBy(): ?int { return $this->performedBy; }
    public function getMovementDate(): ?\DateTimeInterface { return $this->movementDate; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function confirm(): void
    {
        $this->status       = 'confirmed';
        $this->movementDate = $this->movementDate ?? new \DateTimeImmutable;
        $this->updatedAt    = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function updateDetails(?string $notes, ?array $metadataArray, ?string $status): void
    {
        if ($notes !== null) {
            $this->notes = $notes;
        }
        if ($metadataArray !== null) {
            $this->metadata = new Metadata($metadataArray);
        }
        if ($status !== null) {
            $this->status = $status;
        }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
