<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

class SalesReturn
{
    private ?int $id;

    private int $tenantId;

    private int $customerId;

    private ?int $originalSalesOrderId;

    private ?int $originalInvoiceId;

    private ?string $returnNumber;

    private string $status;

    private \DateTimeInterface $returnDate;

    private ?string $returnReason;

    private int $currencyId;

    private string $exchangeRate;

    private string $subtotal;

    private string $taxTotal;

    private string $restockingFeeTotal;

    private string $grandTotal;

    private ?string $creditMemoNumber;

    private ?int $journalEntryId;

    private ?string $notes;

    /** @var array<string, mixed>|null */
    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /** @var SalesReturnLine[] */
    private array $lines = [];

    private const ALLOWED_STATUSES = ['draft', 'approved', 'received', 'closed', 'cancelled'];

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        int $tenantId,
        int $customerId,
        int $currencyId,
        \DateTimeInterface $returnDate,
        ?int $originalSalesOrderId = null,
        ?int $originalInvoiceId = null,
        ?string $returnNumber = null,
        string $status = 'draft',
        ?string $returnReason = null,
        string $exchangeRate = '1.000000',
        string $subtotal = '0.000000',
        string $taxTotal = '0.000000',
        string $restockingFeeTotal = '0.000000',
        string $grandTotal = '0.000000',
        ?string $creditMemoNumber = null,
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
        $this->originalSalesOrderId = $originalSalesOrderId;
        $this->originalInvoiceId = $originalInvoiceId;
        $this->returnNumber = $returnNumber;
        $this->status = $status;
        $this->returnDate = $returnDate;
        $this->returnReason = $returnReason;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->restockingFeeTotal = $restockingFeeTotal;
        $this->grandTotal = $grandTotal;
        $this->creditMemoNumber = $creditMemoNumber;
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

    public function getOriginalSalesOrderId(): ?int
    {
        return $this->originalSalesOrderId;
    }

    public function getOriginalInvoiceId(): ?int
    {
        return $this->originalInvoiceId;
    }

    public function getReturnNumber(): ?string
    {
        return $this->returnNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReturnDate(): \DateTimeInterface
    {
        return $this->returnDate;
    }

    public function getReturnReason(): ?string
    {
        return $this->returnReason;
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

    public function getRestockingFeeTotal(): string
    {
        return $this->restockingFeeTotal;
    }

    public function getGrandTotal(): string
    {
        return $this->grandTotal;
    }

    public function getCreditMemoNumber(): ?string
    {
        return $this->creditMemoNumber;
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

    /** @return SalesReturnLine[] */
    public function getLines(): array
    {
        return $this->lines;
    }

    /** @param SalesReturnLine[] $lines */
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
        \DateTimeInterface $returnDate,
        ?int $originalSalesOrderId = null,
        ?int $originalInvoiceId = null,
        ?string $returnNumber = null,
        ?string $returnReason = null,
        string $exchangeRate = '1.000000',
        string $subtotal = '0.000000',
        string $taxTotal = '0.000000',
        string $restockingFeeTotal = '0.000000',
        string $grandTotal = '0.000000',
        ?string $creditMemoNumber = null,
        ?int $journalEntryId = null,
        ?string $notes = null,
        ?array $metadata = null,
    ): void {
        $this->customerId = $customerId;
        $this->originalSalesOrderId = $originalSalesOrderId;
        $this->originalInvoiceId = $originalInvoiceId;
        $this->returnNumber = $returnNumber;
        $this->returnDate = $returnDate;
        $this->returnReason = $returnReason;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->restockingFeeTotal = $restockingFeeTotal;
        $this->grandTotal = $grandTotal;
        $this->creditMemoNumber = $creditMemoNumber;
        $this->journalEntryId = $journalEntryId;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function approve(): void
    {
        if ($this->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft returns can be approved.');
        }

        $this->status = 'approved';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function receive(): void
    {
        if ($this->status !== 'approved') {
            throw new \InvalidArgumentException('Only approved returns can be received.');
        }

        $this->status = 'received';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function close(): void
    {
        if ($this->status !== 'received') {
            throw new \InvalidArgumentException('Only received returns can be closed.');
        }

        $this->status = 'closed';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function cancel(): void
    {
        if (in_array($this->status, ['received', 'closed'], true)) {
            throw new \InvalidArgumentException('Cannot cancel a return that has been received or closed.');
        }

        $this->status = 'cancelled';
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertStatus(string $status): void
    {
        if (! in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException(
                'Invalid sales return status. Allowed: '.implode(', ', self::ALLOWED_STATUSES)
            );
        }
    }
}
