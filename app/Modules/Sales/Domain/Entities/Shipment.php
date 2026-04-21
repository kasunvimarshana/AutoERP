<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

class Shipment
{
    private ?int $id;

    private int $tenantId;

    private int $customerId;

    private ?int $salesOrderId;

    private int $warehouseId;

    private ?string $shipmentNumber;

    private string $status;

    private ?\DateTimeInterface $shippedDate;

    private ?string $carrier;

    private ?string $trackingNumber;

    private int $currencyId;

    private ?string $notes;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /** @var ShipmentLine[] */
    private array $lines = [];

    private const ALLOWED_STATUSES = ['draft', 'picking', 'packed', 'shipped', 'delivered', 'cancelled'];

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        int $tenantId,
        int $customerId,
        int $warehouseId,
        int $currencyId,
        ?int $salesOrderId = null,
        ?string $shipmentNumber = null,
        string $status = 'draft',
        ?\DateTimeInterface $shippedDate = null,
        ?string $carrier = null,
        ?string $trackingNumber = null,
        ?string $notes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertStatus($status);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->customerId = $customerId;
        $this->salesOrderId = $salesOrderId;
        $this->warehouseId = $warehouseId;
        $this->shipmentNumber = $shipmentNumber;
        $this->status = $status;
        $this->shippedDate = $shippedDate;
        $this->carrier = $carrier;
        $this->trackingNumber = $trackingNumber;
        $this->currencyId = $currencyId;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getSalesOrderId(): ?int
    {
        return $this->salesOrderId;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    public function getShipmentNumber(): ?string
    {
        return $this->shipmentNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getShippedDate(): ?\DateTimeInterface
    {
        return $this->shippedDate;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /** @return ShipmentLine[] */
    public function getLines(): array
    {
        return $this->lines;
    }

    /** @param ShipmentLine[] $lines */
    public function setLines(array $lines): void
    {
        $this->lines = $lines;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function update(
        int $customerId,
        int $warehouseId,
        int $currencyId,
        ?int $salesOrderId = null,
        ?string $shipmentNumber = null,
        ?\DateTimeInterface $shippedDate = null,
        ?string $carrier = null,
        ?string $trackingNumber = null,
        ?string $notes = null,
        ?array $metadata = null,
    ): void {
        $this->customerId = $customerId;
        $this->salesOrderId = $salesOrderId;
        $this->warehouseId = $warehouseId;
        $this->shipmentNumber = $shipmentNumber;
        $this->shippedDate = $shippedDate;
        $this->carrier = $carrier;
        $this->trackingNumber = $trackingNumber;
        $this->currencyId = $currencyId;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function process(): void
    {
        if (! in_array($this->status, ['draft', 'picking', 'packed'], true)) {
            throw new \InvalidArgumentException('Shipment cannot be processed from its current status.');
        }

        $this->status = 'shipped';
        $this->shippedDate = $this->shippedDate ?? new \DateTimeImmutable;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        if (in_array($this->status, ['shipped', 'delivered'], true)) {
            throw new \InvalidArgumentException('Cannot cancel a shipment that has been shipped or delivered.');
        }

        $this->status = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deliver(): void
    {
        if ($this->status !== 'shipped') {
            throw new \InvalidArgumentException('Only shipped shipments can be marked as delivered.');
        }

        $this->status = 'delivered';
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException(
                'Invalid shipment status. Allowed: '.implode(', ', self::ALLOWED_STATUSES)
            );
        }
    }
}
