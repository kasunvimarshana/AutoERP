<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\SalesOrder\Domain\ValueObjects\SalesOrderStatus;

class SalesOrder
{
    private ?int $id;
    private int $tenantId;
    private string $referenceNumber;
    private string $status;
    private int $customerId;
    private ?string $customerReference;
    private string $orderDate;
    private ?string $requiredDate;
    private ?int $warehouseId;
    private string $currency;
    private float $subtotal;
    private float $taxAmount;
    private float $discountAmount;
    private float $totalAmount;
    private ?array $shippingAddress;
    private ?string $notes;
    private Metadata $metadata;
    private ?int $confirmedBy;
    private ?\DateTimeInterface $confirmedAt;
    private ?int $shippedBy;
    private ?\DateTimeInterface $shippedAt;
    private ?\DateTimeInterface $deliveredAt;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $referenceNumber,
        int $customerId,
        string $orderDate,
        ?string $customerReference = null,
        ?string $requiredDate = null,
        ?int $warehouseId = null,
        string $currency = 'USD',
        float $subtotal = 0.0,
        float $taxAmount = 0.0,
        float $discountAmount = 0.0,
        float $totalAmount = 0.0,
        ?array $shippingAddress = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        string $status = SalesOrderStatus::DRAFT,
        ?int $confirmedBy = null,
        ?\DateTimeInterface $confirmedAt = null,
        ?int $shippedBy = null,
        ?\DateTimeInterface $shippedAt = null,
        ?\DateTimeInterface $deliveredAt = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id                = $id;
        $this->tenantId          = $tenantId;
        $this->referenceNumber   = $referenceNumber;
        $this->status            = $status;
        $this->customerId        = $customerId;
        $this->customerReference = $customerReference;
        $this->orderDate         = $orderDate;
        $this->requiredDate      = $requiredDate;
        $this->warehouseId       = $warehouseId;
        $this->currency          = $currency;
        $this->subtotal          = $subtotal;
        $this->taxAmount         = $taxAmount;
        $this->discountAmount    = $discountAmount;
        $this->totalAmount       = $totalAmount;
        $this->shippingAddress   = $shippingAddress;
        $this->notes             = $notes;
        $this->metadata          = $metadata ?? new Metadata([]);
        $this->confirmedBy       = $confirmedBy;
        $this->confirmedAt       = $confirmedAt;
        $this->shippedBy         = $shippedBy;
        $this->shippedAt         = $shippedAt;
        $this->deliveredAt       = $deliveredAt;
        $this->createdAt         = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt         = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function getStatus(): string { return $this->status; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getCustomerReference(): ?string { return $this->customerReference; }
    public function getOrderDate(): string { return $this->orderDate; }
    public function getRequiredDate(): ?string { return $this->requiredDate; }
    public function getWarehouseId(): ?int { return $this->warehouseId; }
    public function getCurrency(): string { return $this->currency; }
    public function getSubtotal(): float { return $this->subtotal; }
    public function getTaxAmount(): float { return $this->taxAmount; }
    public function getDiscountAmount(): float { return $this->discountAmount; }
    public function getTotalAmount(): float { return $this->totalAmount; }
    public function getShippingAddress(): ?array { return $this->shippingAddress; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getConfirmedBy(): ?int { return $this->confirmedBy; }
    public function getConfirmedAt(): ?\DateTimeInterface { return $this->confirmedAt; }
    public function getShippedBy(): ?int { return $this->shippedBy; }
    public function getShippedAt(): ?\DateTimeInterface { return $this->shippedAt; }
    public function getDeliveredAt(): ?\DateTimeInterface { return $this->deliveredAt; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function confirm(int $confirmedBy): void
    {
        $this->status      = SalesOrderStatus::CONFIRMED;
        $this->confirmedBy = $confirmedBy;
        $this->confirmedAt = new \DateTimeImmutable;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function startPicking(): void
    {
        $this->status    = SalesOrderStatus::PICKING;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function startPacking(): void
    {
        $this->status    = SalesOrderStatus::PACKING;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function ship(int $shippedBy): void
    {
        $this->status    = SalesOrderStatus::SHIPPED;
        $this->shippedBy = $shippedBy;
        $this->shippedAt = new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deliver(): void
    {
        $this->status      = SalesOrderStatus::DELIVERED;
        $this->deliveredAt = new \DateTimeImmutable;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = SalesOrderStatus::CANCELLED;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isDraft(): bool { return $this->status === SalesOrderStatus::DRAFT; }
    public function isConfirmed(): bool { return $this->status === SalesOrderStatus::CONFIRMED; }
    public function isCancelled(): bool { return $this->status === SalesOrderStatus::CANCELLED; }

    public function updateDetails(
        ?string $customerReference,
        ?string $requiredDate,
        ?int $warehouseId,
        ?array $shippingAddress,
        ?string $notes,
        ?array $metadataArray,
    ): void {
        if ($customerReference !== null) { $this->customerReference = $customerReference; }
        if ($requiredDate !== null) { $this->requiredDate = $requiredDate; }
        if ($warehouseId !== null) { $this->warehouseId = $warehouseId; }
        if ($shippingAddress !== null) { $this->shippingAddress = $shippingAddress; }
        if ($notes !== null) { $this->notes = $notes; }
        if ($metadataArray !== null) { $this->metadata = new Metadata($metadataArray); }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
