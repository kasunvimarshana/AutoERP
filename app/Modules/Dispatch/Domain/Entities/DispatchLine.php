<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class DispatchLine
{
    private ?int $id;
    private int $tenantId;
    private int $dispatchId;
    private ?int $salesOrderLineId;
    private int $productId;
    private ?int $productVariantId;
    private ?string $description;
    private float $quantity;
    private ?string $unitOfMeasure;
    private ?int $warehouseLocationId;
    private ?string $batchNumber;
    private ?string $serialNumber;
    private string $status;
    private ?float $weight;
    private ?string $notes;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $dispatchId,
        int $productId,
        float $quantity,
        ?int $salesOrderLineId = null,
        ?int $productVariantId = null,
        ?string $description = null,
        ?string $unitOfMeasure = null,
        ?int $warehouseLocationId = null,
        ?string $batchNumber = null,
        ?string $serialNumber = null,
        string $status = 'pending',
        ?float $weight = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id                  = $id;
        $this->tenantId            = $tenantId;
        $this->dispatchId          = $dispatchId;
        $this->salesOrderLineId    = $salesOrderLineId;
        $this->productId           = $productId;
        $this->productVariantId    = $productVariantId;
        $this->description         = $description;
        $this->quantity            = $quantity;
        $this->unitOfMeasure       = $unitOfMeasure;
        $this->warehouseLocationId = $warehouseLocationId;
        $this->batchNumber         = $batchNumber;
        $this->serialNumber        = $serialNumber;
        $this->status              = $status;
        $this->weight              = $weight;
        $this->notes               = $notes;
        $this->metadata            = $metadata ?? new Metadata([]);
        $this->createdAt           = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt           = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getDispatchId(): int { return $this->dispatchId; }
    public function getSalesOrderLineId(): ?int { return $this->salesOrderLineId; }
    public function getProductId(): int { return $this->productId; }
    public function getProductVariantId(): ?int { return $this->productVariantId; }
    public function getDescription(): ?string { return $this->description; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitOfMeasure(): ?string { return $this->unitOfMeasure; }
    public function getWarehouseLocationId(): ?int { return $this->warehouseLocationId; }
    public function getBatchNumber(): ?string { return $this->batchNumber; }
    public function getSerialNumber(): ?string { return $this->serialNumber; }
    public function getStatus(): string { return $this->status; }
    public function getWeight(): ?float { return $this->weight; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function pick(): void
    {
        $this->status    = 'picked';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function pack(): void
    {
        $this->status    = 'packed';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function ship(): void
    {
        $this->status    = 'shipped';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateDetails(
        ?string $description,
        ?float $quantity,
        ?int $warehouseLocationId,
        ?string $batchNumber,
        ?string $serialNumber,
        ?float $weight,
        ?string $notes,
        ?array $metadata,
    ): void {
        if ($description !== null) { $this->description = $description; }
        if ($quantity !== null) { $this->quantity = $quantity; }
        if ($warehouseLocationId !== null) { $this->warehouseLocationId = $warehouseLocationId; }
        if ($batchNumber !== null) { $this->batchNumber = $batchNumber; }
        if ($serialNumber !== null) { $this->serialNumber = $serialNumber; }
        if ($weight !== null) { $this->weight = $weight; }
        if ($notes !== null) { $this->notes = $notes; }
        if ($metadata !== null) { $this->metadata = new Metadata($metadata); }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
