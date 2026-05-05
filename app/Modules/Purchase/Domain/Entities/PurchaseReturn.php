<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

class PurchaseReturn
{
    private ?int $id;

    private int $tenantId;

    private int $supplierId;

    private ?int $originalGrnId;

    private ?int $originalInvoiceId;

    private string $returnNumber;

    private string $status;

    private \DateTimeInterface $returnDate;

    private ?string $returnReason;

    private int $currencyId;

    private string $exchangeRate;

    private string $subtotal;

    private string $taxTotal;

    private string $grandTotal;
    private string $discountTotal;

    private ?string $debitNoteNumber;

    private ?int $journalEntryId;

    private ?string $notes;

    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $supplierId,
        string $returnNumber,
        string $status,
        \DateTimeInterface $returnDate,
        int $currencyId,
        string $exchangeRate,
        ?int $originalGrnId = null,
        ?int $originalInvoiceId = null,
        ?string $returnReason = null,
        string $subtotal = '0',
        string $taxTotal = '0',
        string $grandTotal = '0',
            string $discountTotal = '0',
        ?string $debitNoteNumber = null,
        ?int $journalEntryId = null,
        ?string $notes = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->tenantId = $tenantId;
        $this->supplierId = $supplierId;
        $this->returnNumber = $returnNumber;
        $this->status = $status;
        $this->returnDate = $returnDate;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->originalGrnId = $originalGrnId;
        $this->originalInvoiceId = $originalInvoiceId;
        $this->returnReason = $returnReason;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->grandTotal = $grandTotal;
            $this->discountTotal = $discountTotal;
        $this->debitNoteNumber = $debitNoteNumber;
        $this->journalEntryId = $journalEntryId;
        $this->notes = $notes;
        $this->metadata = $metadata;
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

    public function getOriginalGrnId(): ?int
    {
        return $this->originalGrnId;
    }

    public function getOriginalInvoiceId(): ?int
    {
        return $this->originalInvoiceId;
    }

    public function getReturnNumber(): string
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

    public function getGrandTotal(): string
    {
        return $this->grandTotal;
    }

    public function getDebitNoteNumber(): ?string
    {
        return $this->debitNoteNumber;
    }

    public function getJournalEntryId(): ?int
    {
        return $this->journalEntryId;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

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

    public function update(
        int $supplierId,
        string $returnNumber,
        \DateTimeInterface $returnDate,
        int $currencyId,
        string $exchangeRate,
        ?int $originalGrnId = null,
        ?int $originalInvoiceId = null,
        ?string $returnReason = null,
        string $subtotal = '0',
        string $taxTotal = '0',
        string $grandTotal = '0',
        ?string $debitNoteNumber = null,
        ?int $journalEntryId = null,
        ?string $notes = null,
        ?array $metadata = null,
    ): void {
        $this->supplierId = $supplierId;
        $this->returnNumber = $returnNumber;
        $this->returnDate = $returnDate;
        $this->currencyId = $currencyId;
        $this->exchangeRate = $exchangeRate;
        $this->originalGrnId = $originalGrnId;
        $this->originalInvoiceId = $originalInvoiceId;
        $this->returnReason = $returnReason;
        $this->subtotal = $subtotal;
        $this->taxTotal = $taxTotal;
        $this->grandTotal = $grandTotal;
        $this->debitNoteNumber = $debitNoteNumber;
        $this->journalEntryId = $journalEntryId;
        $this->notes = $notes;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function post(): void
    {
        $this->status = 'approved';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function getDiscountTotal(): string
    {
        return $this->discountTotal;
    }

    public function setJournalEntryId(int $journalEntryId): void
    {
        $this->journalEntryId = $journalEntryId;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
