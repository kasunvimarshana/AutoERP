<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\PurchaseOrder\Domain\ValueObjects\PurchaseOrderStatus;

class PurchaseOrder
{
    private ?int $id;
    private int $tenantId;
    private string $referenceNumber;
    private string $status;
    private int $supplierId;
    private ?string $supplierReference;
    private string $orderDate;
    private ?string $expectedDate;
    private ?int $warehouseId;
    private string $currency;
    private float $subtotal;
    private float $taxAmount;
    private float $discountAmount;
    private float $totalAmount;
    private ?string $notes;
    private Metadata $metadata;
    private ?int $approvedBy;
    private ?\DateTimeInterface $approvedAt;
    private ?int $submittedBy;
    private ?\DateTimeInterface $submittedAt;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $referenceNumber,
        int $supplierId,
        string $orderDate,
        ?string $supplierReference = null,
        ?string $expectedDate = null,
        ?int $warehouseId = null,
        string $currency = 'USD',
        float $subtotal = 0.0,
        float $taxAmount = 0.0,
        float $discountAmount = 0.0,
        float $totalAmount = 0.0,
        ?string $notes = null,
        ?Metadata $metadata = null,
        string $status = PurchaseOrderStatus::DRAFT,
        ?int $approvedBy = null,
        ?\DateTimeInterface $approvedAt = null,
        ?int $submittedBy = null,
        ?\DateTimeInterface $submittedAt = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id                = $id;
        $this->tenantId          = $tenantId;
        $this->referenceNumber   = $referenceNumber;
        $this->status            = $status;
        $this->supplierId        = $supplierId;
        $this->supplierReference = $supplierReference;
        $this->orderDate         = $orderDate;
        $this->expectedDate      = $expectedDate;
        $this->warehouseId       = $warehouseId;
        $this->currency          = $currency;
        $this->subtotal          = $subtotal;
        $this->taxAmount         = $taxAmount;
        $this->discountAmount    = $discountAmount;
        $this->totalAmount       = $totalAmount;
        $this->notes             = $notes;
        $this->metadata          = $metadata ?? new Metadata([]);
        $this->approvedBy        = $approvedBy;
        $this->approvedAt        = $approvedAt;
        $this->submittedBy       = $submittedBy;
        $this->submittedAt       = $submittedAt;
        $this->createdAt         = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt         = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function getStatus(): string { return $this->status; }
    public function getSupplierId(): int { return $this->supplierId; }
    public function getSupplierReference(): ?string { return $this->supplierReference; }
    public function getOrderDate(): string { return $this->orderDate; }
    public function getExpectedDate(): ?string { return $this->expectedDate; }
    public function getWarehouseId(): ?int { return $this->warehouseId; }
    public function getCurrency(): string { return $this->currency; }
    public function getSubtotal(): float { return $this->subtotal; }
    public function getTaxAmount(): float { return $this->taxAmount; }
    public function getDiscountAmount(): float { return $this->discountAmount; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getApprovedBy(): ?int { return $this->approvedBy; }
    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function getSubmittedBy(): ?int { return $this->submittedBy; }
    public function getSubmittedAt(): ?\DateTimeInterface { return $this->submittedAt; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function submit(int $submittedBy): void
    {
        $this->status      = PurchaseOrderStatus::SUBMITTED;
        $this->submittedBy = $submittedBy;
        $this->submittedAt = new \DateTimeImmutable;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function approve(int $approvedBy): void
    {
        $this->status     = PurchaseOrderStatus::APPROVED;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new \DateTimeImmutable;
        $this->updatedAt  = new \DateTimeImmutable;
    }

    public function markPartiallyReceived(): void
    {
        $this->status    = PurchaseOrderStatus::PARTIALLY_RECEIVED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markFullyReceived(): void
    {
        $this->status    = PurchaseOrderStatus::FULLY_RECEIVED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = PurchaseOrderStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function close(): void
    {
        $this->status    = PurchaseOrderStatus::CLOSED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isDraft(): bool { return $this->status === PurchaseOrderStatus::DRAFT; }
    public function isApproved(): bool { return $this->status === PurchaseOrderStatus::APPROVED; }
    public function isCancelled(): bool { return $this->status === PurchaseOrderStatus::CANCELLED; }

    public function updateDetails(
        ?string $supplierReference,
        ?string $expectedDate,
        ?int $warehouseId,
        ?string $notes,
        ?array $metadataArray,
    ): void {
        if ($supplierReference !== null) { $this->supplierReference = $supplierReference; }
        if ($expectedDate !== null) { $this->expectedDate = $expectedDate; }
        if ($warehouseId !== null) { $this->warehouseId = $warehouseId; }
        if ($notes !== null) { $this->notes = $notes; }
        if ($metadataArray !== null) { $this->metadata = new Metadata($metadataArray); }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
