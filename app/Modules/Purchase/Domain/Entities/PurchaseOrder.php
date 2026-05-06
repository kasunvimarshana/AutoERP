<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

class PurchaseOrder
{
    private ?int $id;

    private int $tenantId;

    private int $supplierId;

    private ?int $orgUnitId;

    private int $warehouseId;

    private string $poNumber;

    private string $status;

    private int $currencyId;

    private string $exchangeRate;

    private \DateTimeInterface $orderDate;

    private ?\DateTimeInterface $expectedDate;

    private string $subtotal;

    private string $taxTotal;

    private string $discountTotal;

    private string $grandTotal;

    private ?string $notes;

    private ?array $metadata;

    private int $createdBy;

    private ?int $approvedBy;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $supplierId,
        int $warehouseId,
        string $poNumber,
        string $status,
        int $currencyId,
        string $exchangeRate,
        \DateTimeInterface $orderDate,
        int $createdBy,
        ?int $orgUnitId = null,
        ?\DateTimeInterface $expectedDate = null,
        string $subtotal = '0',
        string $taxTotal = '0',
        string $discountTotal = '0',
        string $grandTotal = '0',
        ?string $notes = null,
        ?array $metadata = null,
        ?int $approvedBy = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->tenantId = $tenantId;
        $this->supplierId = $supplierId;
        $this->warehouseId = $warehouseId;
        $this->poNumber = $poNumber;
        $this->status = $status;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->orderDate = $orderDate;
        $this->createdBy = $createdBy;
        $this->orgUnitId = $orgUnitId;
        $this->expectedDate = $expectedDate;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->discountTotal = $discountTotal;
        $this->grandTotal = $grandTotal;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->approvedBy = $approvedBy;
        $this->id = $id;
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

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    public function getPoNumber(): string
    {
        return $this->poNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getExchangeRate(): string
    {
        return $this->exchangeRate;
    }

    public function getOrderDate(): \DateTimeInterface
    {
        return $this->orderDate;
    }

    public function getExpectedDate(): ?\DateTimeInterface
    {
        return $this->expectedDate;
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function getTaxTotal(): string
    {
        return $this->taxTotal;
    }

    public function getDiscountTotal(): string
    {
        return $this->discountTotal;
    }

    public function getGrandTotal(): string
    {
        return $this->grandTotal;
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

    public function getApprovedBy(): ?int
    {
        return $this->approvedBy;
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
        string $poNumber,
        int $currencyId,
        string $exchangeRate,
        \DateTimeInterface $orderDate,
        ?int $orgUnitId = null,
        ?\DateTimeInterface $expectedDate = null,
        string $subtotal = '0',
        string $taxTotal = '0',
        string $discountTotal = '0',
        string $grandTotal = '0',
        ?string $notes = null,
        ?array $metadata = null,
        ?int $approvedBy = null,
    ): void {
        $this->supplierId = $supplierId;
        $this->warehouseId = $warehouseId;
        $this->poNumber = $poNumber;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->orderDate = $orderDate;
        $this->orgUnitId = $orgUnitId;
        $this->expectedDate = $expectedDate;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->discountTotal = $discountTotal;
        $this->grandTotal = $grandTotal;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->approvedBy = $approvedBy;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function confirm(): void
    {
        $this->status = 'confirmed';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function send(): void
    {
        $this->status = 'sent';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function close(): void
    {
        $this->status = 'closed';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function receive(): void
    {
        $this->status = 'received';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markPartial(): void
    {
        $this->status = 'partial';
        $this->updatedAt = new \DateTimeImmutable;
    }
}
