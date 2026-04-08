<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\SalesOrder\Domain\ValueObjects\SalesOrderLineStatus;

class SalesOrderLine
{
    private ?int $id;
    private int $tenantId;
    private int $salesOrderId;
    private int $productId;
    private ?int $productVariantId;
    private ?string $description;
    private float $quantity;
    private float $unitPrice;
    private float $taxRate;
    private float $discountAmount;
    private float $totalAmount;
    private ?string $unitOfMeasure;
    private string $status;
    private ?int $warehouseLocationId;
    private ?string $batchNumber;
    private ?string $serialNumber;
    private ?string $notes;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $salesOrderId,
        int $productId,
        float $quantity,
        float $unitPrice,
        ?int $productVariantId = null,
        ?string $description = null,
        float $taxRate = 0.0,
        float $discountAmount = 0.0,
        float $totalAmount = 0.0,
        ?string $unitOfMeasure = null,
        string $status = SalesOrderLineStatus::PENDING,
        ?int $warehouseLocationId = null,
        ?string $batchNumber = null,
        ?string $serialNumber = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id                  = $id;
        $this->tenantId            = $tenantId;
        $this->salesOrderId        = $salesOrderId;
        $this->productId           = $productId;
        $this->productVariantId    = $productVariantId;
        $this->description         = $description;
        $this->quantity            = $quantity;
        $this->unitPrice           = $unitPrice;
        $this->taxRate             = $taxRate;
        $this->discountAmount      = $discountAmount;
        $this->totalAmount         = $totalAmount;
        $this->unitOfMeasure       = $unitOfMeasure;
        $this->status              = $status;
        $this->warehouseLocationId = $warehouseLocationId;
        $this->batchNumber         = $batchNumber;
        $this->serialNumber        = $serialNumber;
        $this->notes               = $notes;
        $this->metadata            = $metadata ?? new Metadata([]);
        $this->createdAt           = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt           = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getSalesOrderId(): int { return $this->salesOrderId; }
    public function getProductId(): int { return $this->productId; }
    public function getProductVariantId(): ?int { return $this->productVariantId; }
    public function getDescription(): ?string { return $this->description; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getTaxRate(): float { return $this->taxRate; }
    public function getDiscountAmount(): float { return $this->discountAmount; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getUnitOfMeasure(): ?string { return $this->unitOfMeasure; }
    public function getStatus(): string { return $this->status; }
    public function getWarehouseLocationId(): ?int { return $this->warehouseLocationId; }
    public function getBatchNumber(): ?string { return $this->batchNumber; }
    public function getSerialNumber(): ?string { return $this->serialNumber; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function startPicking(): void
    {
        $this->status    = SalesOrderLineStatus::PICKING;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function pack(): void
    {
        $this->status    = SalesOrderLineStatus::PACKED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function dispatch(): void
    {
        $this->status    = SalesOrderLineStatus::DISPATCHED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = SalesOrderLineStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function approve(): void
    {
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function passQualityCheck(): void
    {
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateDetails(
        float $quantity,
        float $unitPrice,
        float $taxRate,
        float $discountAmount,
        float $totalAmount,
        ?int $warehouseLocationId,
        ?string $batchNumber,
        ?string $serialNumber,
        ?string $description,
        ?string $notes,
        ?array $metadataArray,
    ): void {
        $this->quantity       = $quantity;
        $this->unitPrice      = $unitPrice;
        $this->taxRate        = $taxRate;
        $this->discountAmount = $discountAmount;
        $this->totalAmount    = $totalAmount;
        if ($warehouseLocationId !== null) { $this->warehouseLocationId = $warehouseLocationId; }
        if ($batchNumber !== null) { $this->batchNumber = $batchNumber; }
        if ($serialNumber !== null) { $this->serialNumber = $serialNumber; }
        if ($description !== null) { $this->description = $description; }
        if ($notes !== null) { $this->notes = $notes; }
        if ($metadataArray !== null) { $this->metadata = new Metadata($metadataArray); }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
