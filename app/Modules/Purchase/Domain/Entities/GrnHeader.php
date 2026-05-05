<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

class GrnHeader
{
    private ?int $id;

    private int $tenantId;

    private int $supplierId;

    private int $warehouseId;

    private ?int $purchaseOrderId;

    private string $grnNumber;

    private string $status;

    private \DateTimeInterface $receivedDate;

    private int $currencyId;

    private string $exchangeRate;

    private string $subtotal;

    private string $taxTotal;

    private string $grandTotal;

    private ?string $notes;

    private ?array $metadata;

    private int $createdBy;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $supplierId,
        int $warehouseId,
        string $grnNumber,
        string $status,
        \DateTimeInterface $receivedDate,
        int $currencyId,
        string $exchangeRate,
        int $createdBy,
        ?int $purchaseOrderId = null,
        ?string $notes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
        string $subtotal = '0',
        string $taxTotal = '0',
        string $grandTotal = '0',
    ) {
        $this->tenantId = $tenantId;
        $this->supplierId = $supplierId;
        $this->warehouseId = $warehouseId;
        $this->grnNumber = $grnNumber;
        $this->status = $status;
        $this->receivedDate = $receivedDate;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->createdBy = $createdBy;
        $this->purchaseOrderId = $purchaseOrderId;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->grandTotal = $grandTotal;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    public function getPurchaseOrderId(): ?int
    {
        return $this->purchaseOrderId;
    }

    public function getGrnNumber(): string
    {
        return $this->grnNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReceivedDate(): \DateTimeInterface
    {
        return $this->receivedDate;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getExchangeRate(): string
    {
        return $this->exchangeRate;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(
        int $supplierId,
        int $warehouseId,
        string $grnNumber,
        \DateTimeInterface $receivedDate,
        int $currencyId,
        string $exchangeRate,
        ?int $purchaseOrderId = null,
        ?string $notes = null,
        ?array $metadata = null,
    ): void {
        $this->supplierId = $supplierId;
        $this->warehouseId = $warehouseId;
        $this->grnNumber = $grnNumber;
        $this->receivedDate = $receivedDate;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->purchaseOrderId = $purchaseOrderId;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function getTaxTotal(): string
    {
        return $this->taxTotal;
    }

    public function getGrandTotal(): string
    {
        return $this->grandTotal;
    }

    public function setTotals(string $subtotal, string $taxTotal, string $grandTotal): void
    {
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->grandTotal = $grandTotal;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function post(): void
    {
        $this->status = 'posted';
        $this->updatedAt = new \DateTimeImmutable;
    }

}
