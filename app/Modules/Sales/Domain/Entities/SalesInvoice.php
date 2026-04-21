<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

class SalesInvoice
{
    private ?int $id;

    private int $tenantId;

    private int $customerId;

    private ?int $salesOrderId;

    private ?int $shipmentId;

    private ?string $invoiceNumber;

    private string $status;

    private \DateTimeInterface $invoiceDate;

    private \DateTimeInterface $dueDate;

    private int $currencyId;

    private string $exchangeRate;

    private string $subtotal;

    private string $taxTotal;

    private string $discountTotal;

    private string $grandTotal;

    private ?int $arAccountId;

    private ?int $journalEntryId;

    private ?string $notes;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /** @var SalesInvoiceLine[] */
    private array $lines = [];

    private const ALLOWED_STATUSES = ['draft', 'sent', 'partial_paid', 'paid', 'overdue', 'cancelled'];

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        int $tenantId,
        int $customerId,
        int $currencyId,
        \DateTimeInterface $invoiceDate,
        \DateTimeInterface $dueDate,
        ?int $salesOrderId = null,
        ?int $shipmentId = null,
        ?string $invoiceNumber = null,
        string $status = 'draft',
        string $exchangeRate = '1.000000',
        string $subtotal = '0.000000',
        string $taxTotal = '0.000000',
        string $discountTotal = '0.000000',
        string $grandTotal = '0.000000',
        ?int $arAccountId = null,
        ?int $journalEntryId = null,
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
        $this->shipmentId = $shipmentId;
        $this->invoiceNumber = $invoiceNumber;
        $this->status = $status;
        $this->invoiceDate = $invoiceDate;
        $this->dueDate = $dueDate;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->discountTotal = $discountTotal;
        $this->grandTotal = $grandTotal;
        $this->arAccountId = $arAccountId;
        $this->journalEntryId = $journalEntryId;
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

    public function getShipmentId(): ?int
    {
        return $this->shipmentId;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getInvoiceDate(): \DateTimeInterface
    {
        return $this->invoiceDate;
    }

    public function getDueDate(): \DateTimeInterface
    {
        return $this->dueDate;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function getExchangeRate(): string
    {
        return $this->exchangeRate;
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

    public function getArAccountId(): ?int
    {
        return $this->arAccountId;
    }

    public function getJournalEntryId(): ?int
    {
        return $this->journalEntryId;
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

    /** @return SalesInvoiceLine[] */
    public function getLines(): array
    {
        return $this->lines;
    }

    /** @param SalesInvoiceLine[] $lines */
    public function setLines(array $lines): void
    {
        $this->lines = $lines;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function update(
        int $customerId,
        int $currencyId,
        \DateTimeInterface $invoiceDate,
        \DateTimeInterface $dueDate,
        ?int $salesOrderId = null,
        ?int $shipmentId = null,
        ?string $invoiceNumber = null,
        string $exchangeRate = '1.000000',
        string $subtotal = '0.000000',
        string $taxTotal = '0.000000',
        string $discountTotal = '0.000000',
        string $grandTotal = '0.000000',
        ?int $arAccountId = null,
        ?int $journalEntryId = null,
        ?string $notes = null,
        ?array $metadata = null,
    ): void {
        $this->customerId = $customerId;
        $this->salesOrderId = $salesOrderId;
        $this->shipmentId = $shipmentId;
        $this->invoiceNumber = $invoiceNumber;
        $this->invoiceDate = $invoiceDate;
        $this->dueDate = $dueDate;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->discountTotal = $discountTotal;
        $this->grandTotal = $grandTotal;
        $this->arAccountId = $arAccountId;
        $this->journalEntryId = $journalEntryId;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function post(): void
    {
        if ($this->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft invoices can be posted.');
        }

        $this->status = 'sent';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        if (in_array($this->status, ['paid', 'cancelled'], true)) {
            throw new \InvalidArgumentException('Cannot cancel a paid or already cancelled invoice.');
        }

        $this->status = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markPaid(): void
    {
        if (! in_array($this->status, ['sent', 'partial_paid', 'overdue'], true)) {
            throw new \InvalidArgumentException('Invoice cannot be marked as paid from its current status.');
        }

        $this->status = 'paid';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markPartialPaid(): void
    {
        if (! in_array($this->status, ['sent', 'overdue'], true)) {
            throw new \InvalidArgumentException('Invoice cannot be marked as partial paid from its current status.');
        }

        $this->status = 'partial_paid';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function markOverdue(): void
    {
        if (! in_array($this->status, ['sent', 'partial_paid'], true)) {
            throw new \InvalidArgumentException('Invoice cannot be marked as overdue from its current status.');
        }

        $this->status = 'overdue';
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException(
                'Invalid sales invoice status. Allowed: '.implode(', ', self::ALLOWED_STATUSES)
            );
        }
    }
}
