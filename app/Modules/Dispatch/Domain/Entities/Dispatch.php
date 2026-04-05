<?php
declare(strict_types=1);
namespace Modules\Dispatch\Domain\Entities;
class Dispatch {
    public function __construct(
        private ?int $id, private int $tenantId, private int $salesOrderId,
        private int $warehouseId, private string $dispatchNumber, private string $status,
        private ?string $carrier, private ?string $trackingNumber, private ?float $shippingCost,
        private array $lines,
        private ?\DateTimeInterface $shippedAt, private ?\DateTimeInterface $deliveredAt,
        private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getSalesOrderId(): int { return $this->salesOrderId; }
    public function getWarehouseId(): int { return $this->warehouseId; }
    public function getDispatchNumber(): string { return $this->dispatchNumber; }
    public function getStatus(): string { return $this->status; }
    public function getCarrier(): ?string { return $this->carrier; }
    public function getTrackingNumber(): ?string { return $this->trackingNumber; }
    public function getShippingCost(): ?float { return $this->shippingCost; }
    public function getLines(): array { return $this->lines; }
    public function getShippedAt(): ?\DateTimeInterface { return $this->shippedAt; }
    public function getDeliveredAt(): ?\DateTimeInterface { return $this->deliveredAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function ship(string $carrier, string $trackingNumber): void {
        if ($this->status !== 'pending') throw new \DomainException("Can only ship a pending dispatch.");
        $this->status = 'shipped'; $this->carrier = $carrier; $this->trackingNumber = $trackingNumber;
        $this->shippedAt = new \DateTimeImmutable();
    }
    public function markDelivered(): void {
        if ($this->status !== 'shipped') throw new \DomainException("Dispatch must be shipped before delivery.");
        $this->status = 'delivered'; $this->deliveredAt = new \DateTimeImmutable();
    }
}
