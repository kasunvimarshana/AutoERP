<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class CreditMemo
{
    public function __construct(
        private int $tenantId,
        private int $partyId,
        private string $partyType,
        private string $creditMemoNumber,
        private float $amount,
        private \DateTimeInterface $issuedDate,
        private string $status = 'draft',
        private ?int $returnOrderId = null,
        private ?string $returnOrderType = null,
        private ?int $appliedToInvoiceId = null,
        private ?string $appliedToInvoiceType = null,
        private ?string $notes = null,
        private ?int $journalEntryId = null,
        private ?int $id = null,
        private ?\DateTimeInterface $createdAt = null,
        private ?\DateTimeInterface $updatedAt = null,
    ) {
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

    public function getPartyId(): int
    {
        return $this->partyId;
    }

    public function getPartyType(): string
    {
        return $this->partyType;
    }

    public function getCreditMemoNumber(): string
    {
        return $this->creditMemoNumber;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getIssuedDate(): \DateTimeInterface
    {
        return $this->issuedDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReturnOrderId(): ?int
    {
        return $this->returnOrderId;
    }

    public function getReturnOrderType(): ?string
    {
        return $this->returnOrderType;
    }

    public function getAppliedToInvoiceId(): ?int
    {
        return $this->appliedToInvoiceId;
    }

    public function getAppliedToInvoiceType(): ?string
    {
        return $this->appliedToInvoiceType;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getJournalEntryId(): ?int
    {
        return $this->journalEntryId;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function issue(): void
    {
        $this->status = 'issued';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function apply(int $invoiceId, string $invoiceType): void
    {
        $this->status = 'applied';
        $this->appliedToInvoiceId = $invoiceId;
        $this->appliedToInvoiceType = $invoiceType;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function void(): void
    {
        $this->status = 'voided';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function linkJournalEntry(int $journalEntryId): void
    {
        $this->journalEntryId = $journalEntryId;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
