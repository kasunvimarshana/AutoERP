<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

class SalesOrder
{
    private ?int $id;

    private int $tenantId;

    private int $customerId;

    private ?int $orgUnitId;

    private int $warehouseId;

    private ?string $soNumber;

    private string $status;

    private int $currencyId;

    private string $exchangeRate;

    private \DateTimeInterface $orderDate;

    private ?\DateTimeInterface $requestedDeliveryDate;

    private ?int $priceListId;

    private string $subtotal;

    private string $taxTotal;

    private string $discountTotal;

    private string $grandTotal;

    private ?string $notes;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private ?int $createdBy;

    private ?int $approvedBy;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /** @var SalesOrderLine[] */
    private array $lines = [];

    private const ALLOWED_STATUSES = ['draft', 'confirmed', 'partial', 'shipped', 'invoiced', 'closed', 'cancelled'];

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        int $tenantId,
        int $customerId,
        int $warehouseId,
        int $currencyId,
        \DateTimeInterface $orderDate,
        ?int $orgUnitId = null,
        ?string $soNumber = null,
        string $status = 'draft',
        string $exchangeRate = '1.000000',
        ?\DateTimeInterface $requestedDeliveryDate = null,
        ?int $priceListId = null,
        string $subtotal = '0.000000',
        string $taxTotal = '0.000000',
        string $discountTotal = '0.000000',
        string $grandTotal = '0.000000',
        ?string $notes = null,
        ?array $metadata = null,
        ?int $createdBy = null,
        ?int $approvedBy = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertStatus($status);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->customerId = $customerId;
        $this->orgUnitId = $orgUnitId;
        $this->warehouseId = $warehouseId;
        $this->soNumber = $soNumber;
        $this->status = $status;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->orderDate = $orderDate;
        $this->requestedDeliveryDate = $requestedDeliveryDate;
        $this->priceListId = $priceListId;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->discountTotal = $discountTotal;
        $this->grandTotal = $grandTotal;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->createdBy = $createdBy;
        $this->approvedBy = $approvedBy;
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

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    public function getSoNumber(): ?string
    {
        return $this->soNumber;
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

    public function getRequestedDeliveryDate(): ?\DateTimeInterface
    {
        return $this->requestedDeliveryDate;
    }

    public function getPriceListId(): ?int
    {
        return $this->priceListId;
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

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCreatedBy(): ?int
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

    /** @return SalesOrderLine[] */
    public function getLines(): array
    {
        return $this->lines;
    }

    /** @param SalesOrderLine[] $lines */
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
        \DateTimeInterface $orderDate,
        ?int $orgUnitId = null,
        ?string $soNumber = null,
        string $exchangeRate = '1.000000',
        ?\DateTimeInterface $requestedDeliveryDate = null,
        ?int $priceListId = null,
        string $subtotal = '0.000000',
        string $taxTotal = '0.000000',
        string $discountTotal = '0.000000',
        string $grandTotal = '0.000000',
        ?string $notes = null,
        ?array $metadata = null,
        ?int $createdBy = null,
        ?int $approvedBy = null,
    ): void {
        $this->customerId = $customerId;
        $this->orgUnitId = $orgUnitId;
        $this->warehouseId = $warehouseId;
        $this->soNumber = $soNumber;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->orderDate = $orderDate;
        $this->requestedDeliveryDate = $requestedDeliveryDate;
        $this->priceListId = $priceListId;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->discountTotal = $discountTotal;
        $this->grandTotal = $grandTotal;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->createdBy = $createdBy;
        $this->approvedBy = $approvedBy;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function confirm(): void
    {
        if ($this->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft orders can be confirmed.');
        }

        $this->status = 'confirmed';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        if (in_array($this->status, ['shipped', 'invoiced', 'closed'], true)) {
            throw new \InvalidArgumentException('Cannot cancel an order that has been shipped, invoiced, or closed.');
        }

        $this->status = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markShipped(): void
    {
        if (! in_array($this->status, ['confirmed', 'partial'], true)) {
            throw new \InvalidArgumentException('Only confirmed or partial orders can be marked as shipped.');
        }

        $this->status = 'shipped';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markInvoiced(): void
    {
        if (! in_array($this->status, ['shipped', 'partial'], true)) {
            throw new \InvalidArgumentException('Only shipped or partial orders can be marked as invoiced.');
        }

        $this->status = 'invoiced';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function close(): void
    {
        if ($this->status !== 'invoiced') {
            throw new \InvalidArgumentException('Only invoiced orders can be closed.');
        }

        $this->status = 'closed';
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException(
                'Invalid sales order status. Allowed: '.implode(', ', self::ALLOWED_STATUSES)
            );
        }
    }
}
