<?php

declare(strict_types=1);

namespace Modules\Dispatch\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class Dispatch
{
    private ?int $id;
    private int $tenantId;
    private string $referenceNumber;
    private string $status;
    private int $warehouseId;
    private ?int $salesOrderId;
    private int $customerId;
    private ?string $customerReference;
    private string $dispatchDate;
    private ?string $estimatedDeliveryDate;
    private ?string $actualDeliveryDate;
    private ?string $carrier;
    private ?string $trackingNumber;
    private string $currency;
    private ?float $totalWeight;
    private ?string $notes;
    private Metadata $metadata;
    private ?int $confirmedBy;
    private ?\DateTimeInterface $confirmedAt;
    private ?int $shippedBy;
    private ?\DateTimeInterface $shippedAt;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $referenceNumber,
        int $warehouseId,
        int $customerId,
        string $dispatchDate,
        ?int $salesOrderId = null,
        ?string $customerReference = null,
        ?string $estimatedDeliveryDate = null,
        ?string $actualDeliveryDate = null,
        ?string $carrier = null,
        ?string $trackingNumber = null,
        string $currency = 'USD',
        ?float $totalWeight = null,
        ?string $notes = null,
        ?Metadata $metadata = null,
        string $status = 'draft',
        ?int $confirmedBy = null,
        ?\DateTimeInterface $confirmedAt = null,
        ?int $shippedBy = null,
        ?\DateTimeInterface $shippedAt = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id                    = $id;
        $this->tenantId              = $tenantId;
        $this->referenceNumber       = $referenceNumber;
        $this->status                = $status;
        $this->warehouseId           = $warehouseId;
        $this->salesOrderId          = $salesOrderId;
        $this->customerId            = $customerId;
        $this->customerReference     = $customerReference;
        $this->dispatchDate          = $dispatchDate;
        $this->estimatedDeliveryDate = $estimatedDeliveryDate;
        $this->actualDeliveryDate    = $actualDeliveryDate;
        $this->carrier               = $carrier;
        $this->trackingNumber        = $trackingNumber;
        $this->currency              = $currency;
        $this->totalWeight           = $totalWeight;
        $this->notes                 = $notes;
        $this->metadata              = $metadata ?? new Metadata([]);
        $this->confirmedBy           = $confirmedBy;
        $this->confirmedAt           = $confirmedAt;
        $this->shippedBy             = $shippedBy;
        $this->shippedAt             = $shippedAt;
        $this->createdAt             = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt             = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getReferenceNumber(): string { return $this->referenceNumber; }
    public function getStatus(): string { return $this->status; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getSalesOrderId(): ?int { return $this->salesOrderId; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getCustomerReference(): ?string { return $this->customerReference; }
    public function getDispatchDate(): string { return $this->dispatchDate; }
    public function getEstimatedDeliveryDate(): ?string { return $this->estimatedDeliveryDate; }
    public function getActualDeliveryDate(): ?string { return $this->actualDeliveryDate; }
    public function getCarrier(): ?string { return $this->carrier; }
    public function getTrackingNumber(): ?string { return $this->trackingNumber; }
    public function getCurrency(): string { return $this->currency; }
    public function getTotalWeight(): ?float { return $this->totalWeight; }
    public function getNotes(): ?string { return $this->notes; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getConfirmedBy(): ?int { return $this->confirmedBy; }
    public function getConfirmedAt(): ?\DateTimeInterface { return $this->confirmedAt; }
    public function getShippedBy(): ?int { return $this->shippedBy; }
    public function getShippedAt(): ?\DateTimeInterface { return $this->shippedAt; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function confirm(int $confirmedBy): void
    {
        $this->status      = 'confirmed';
        $this->confirmedBy = $confirmedBy;
        $this->confirmedAt = new \DateTimeImmutable;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function ship(int $shippedBy, ?string $trackingNumber = null): void
    {
        $this->status    = 'in_transit';
        $this->shippedBy = $shippedBy;
        $this->shippedAt = new \DateTimeImmutable;
        if ($trackingNumber !== null) {
            $this->trackingNumber = $trackingNumber;
        }
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deliver(?string $actualDeliveryDate = null): void
    {
        $this->status             = 'delivered';
        $this->actualDeliveryDate = $actualDeliveryDate ?? (new \DateTimeImmutable)->format('Y-m-d');
        $this->updatedAt          = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status    = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isConfirmed(): bool { return $this->status === 'confirmed'; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }

    public function updateDetails(
        ?string $customerReference,
        ?string $estimatedDeliveryDate,
        ?string $carrier,
        ?string $trackingNumber,
        ?string $notes,
        ?array $metadataArray,
        ?float $totalWeight,
    ): void {
        if ($customerReference !== null) { $this->customerReference = $customerReference; }
        if ($estimatedDeliveryDate !== null) { $this->estimatedDeliveryDate = $estimatedDeliveryDate; }
        if ($carrier !== null) { $this->carrier = $carrier; }
        if ($trackingNumber !== null) { $this->trackingNumber = $trackingNumber; }
        if ($notes !== null) { $this->notes = $notes; }
        if ($metadataArray !== null) { $this->metadata = new Metadata($metadataArray); }
        if ($totalWeight !== null) { $this->totalWeight = $totalWeight; }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
