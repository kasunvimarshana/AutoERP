<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\GoodsReceipt\Domain\ValueObjects\GoodsReceiptStatus;

class GoodsReceipt
{
    private ?int $id;
    private int $tenantId;
    private string $referenceNumber;
    private string $status;
    private ?int $purchaseOrderId;
    private int $supplierId;
    private ?int $warehouseId;
    private ?\DateTimeInterface $receivedDate;
    private string $currency;
    private ?string $notes;
    private Metadata $metadata;
    private ?int $receivedBy;
    private ?int $approvedBy;
    private ?\DateTimeInterface $approvedAt;
    private ?int $inspectedBy;
    private ?\DateTimeInterface $inspectedAt;
    private ?int $putAwayBy;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $referenceNumber,
        int $supplierId,
        ?int $purchaseOrderId = null,
        ?int $warehouseId = null,
        ?\DateTimeInterface $receivedDate = null,
        string $currency = 'USD',
        ?string $notes = null,
        ?Metadata $metadata = null,
        string $status = 'draft',
        ?int $receivedBy = null,
        ?int $approvedBy = null,
        ?\DateTimeInterface $approvedAt = null,
        ?int $inspectedBy = null,
        ?\DateTimeInterface $inspectedAt = null,
        ?int $putAwayBy = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id              = $id;
        $this->tenantId        = $tenantId;
        $this->referenceNumber = $referenceNumber;
        $this->status          = $status;
        $this->purchaseOrderId = $purchaseOrderId;
        $this->supplierId      = $supplierId;
        $this->warehouseId     = $warehouseId;
        $this->receivedDate    = $receivedDate;
        $this->currency        = $currency;
        $this->notes           = $notes;
        $this->metadata        = $metadata ?? new Metadata([]);
        $this->receivedBy      = $receivedBy;
        $this->approvedBy      = $approvedBy;
        $this->approvedAt      = $approvedAt;
        $this->inspectedBy     = $inspectedBy;
        $this->inspectedAt     = $inspectedAt;
        $this->putAwayBy       = $putAwayBy;
        $this->createdAt       = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt       = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function getStatus(): string { return $this->status; }
    public function getPurchaseOrderId(): ?int { return $this->purchaseOrderId; }
    public function getSupplierId(): int { return $this->supplierId; }
    public function getWarehouseId(): ?int { return $this->warehouseId; }
    public function getReceivedDate(): ?\DateTimeInterface { return $this->receivedDate; }
    public function getCurrency(): string { return $this->currency; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getReceivedBy(): ?int { return $this->receivedBy; }
    public function getApprovedBy(): ?int { return $this->approvedBy; }
    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function getInspectedBy(): ?int { return $this->inspectedBy; }
    public function getInspectedAt(): ?\DateTimeInterface { return $this->inspectedAt; }
    public function getPutAwayBy(): ?int { return $this->putAwayBy; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function receive(int $receivedBy): void
    {
        $this->status     = GoodsReceiptStatus::PENDING;
        $this->receivedBy = $receivedBy;
        $this->updatedAt  = new \DateTimeImmutable;
    }

    public function approve(int $approvedBy): void
    {
        $this->status     = GoodsReceiptStatus::APPROVED;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new \DateTimeImmutable;
        $this->updatedAt  = new \DateTimeImmutable;
    }

    public function markPartiallyReceived(): void
    {
        $this->status    = GoodsReceiptStatus::PARTIALLY_RECEIVED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markFullyReceived(): void
    {
        $this->status    = GoodsReceiptStatus::FULLY_RECEIVED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function inspect(int $inspectedBy): void
    {
        $this->status      = GoodsReceiptStatus::INSPECTED;
        $this->inspectedBy = $inspectedBy;
        $this->inspectedAt = new \DateTimeImmutable;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function putAway(int $putAwayBy): void
    {
        $this->status    = GoodsReceiptStatus::PUT_AWAY;
        $this->putAwayBy = $putAwayBy;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = GoodsReceiptStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isDraft(): bool { return $this->status === GoodsReceiptStatus::DRAFT; }
    public function isApproved(): bool { return $this->status === GoodsReceiptStatus::APPROVED; }
    public function isInspected(): bool { return $this->status === GoodsReceiptStatus::INSPECTED; }
    public function isCancelled(): bool { return $this->status === GoodsReceiptStatus::CANCELLED; }
    public function isPutAway(): bool { return $this->status === GoodsReceiptStatus::PUT_AWAY; }

    public function updateDetails(?string $notes, ?array $metadata, ?int $warehouseId = null, ?\DateTimeInterface $receivedDate = null): void
    {
        if ($notes !== null) { $this->notes = $notes; }
        if ($metadata !== null) { $this->metadata = new Metadata($metadata); }
        if ($warehouseId !== null) { $this->warehouseId = $warehouseId; }
        if ($receivedDate !== null) { $this->receivedDate = $receivedDate; }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
