<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class InventoryValuationLayer
{
    private ?int $id;
    private int $tenantId;
    private int $productId;
    private ?int $variationId;
    private ?int $batchId;
    private ?int $locationId;
    private \DateTimeInterface $layerDate;
    private float $qtyIn;
    private float $qtyRemaining;
    private float $unitCost;
    private string $currency;
    private string $valuationMethod;
    private ?string $referenceType;
    private ?int $referenceId;
    private bool $isClosed;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $productId,
        \DateTimeInterface $layerDate,
        float $qtyIn,
        float $unitCost,
        string $valuationMethod,
        ?int $variationId = null,
        ?int $batchId = null,
        ?int $locationId = null,
        float $qtyRemaining = 0.0,
        string $currency = 'USD',
        ?string $referenceType = null,
        ?int $referenceId = null,
        bool $isClosed = false,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id              = $id;
        $this->tenantId        = $tenantId;
        $this->productId       = $productId;
        $this->variationId     = $variationId;
        $this->batchId         = $batchId;
        $this->locationId      = $locationId;
        $this->layerDate       = $layerDate;
        $this->qtyIn           = $qtyIn;
        $this->qtyRemaining    = $qtyRemaining > 0.0 ? $qtyRemaining : $qtyIn;
        $this->unitCost        = $unitCost;
        $this->currency        = $currency;
        $this->valuationMethod = $valuationMethod;
        $this->referenceType   = $referenceType;
        $this->referenceId     = $referenceId;
        $this->isClosed        = $isClosed;
        $this->metadata        = $metadata ?? new Metadata([]);
        $this->createdAt       = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt       = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getProductId(): int { return $this->productId; }
    public function getVariationId(): ?int { return $this->variationId; }
    public function getBatchId(): ?int { return $this->batchId; }
    public function getLocationId(): ?int { return $this->locationId; }
    public function getLayerDate(): \DateTimeInterface { return $this->layerDate; }
    public function getQtyIn(): float { return $this->qtyIn; }
    public function getQtyRemaining(): float { return $this->qtyRemaining; }
    public function getUnitCost(): float { return $this->unitCost; }
    public function getCurrency(): string { return $this->currency; }
    public function getValuationMethod(): string { return $this->valuationMethod; }
    public function getReferenceType(): ?string { return $this->referenceType; }
    public function getReferenceId(): ?int { return $this->referenceId; }
    public function isClosed(): bool { return $this->isClosed; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function consume(float $qty): float
    {
        $consumed           = min($qty, $this->qtyRemaining);
        $this->qtyRemaining -= $consumed;
        if ($this->qtyRemaining <= 0.0) {
            $this->qtyRemaining = 0.0;
            $this->isClosed     = true;
        }
        $this->updatedAt = new \DateTimeImmutable;

        return $consumed;
    }

    public function getTotalValue(): float
    {
        return $this->qtyRemaining * $this->unitCost;
    }
}
