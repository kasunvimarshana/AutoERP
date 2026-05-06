<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Entities;

class BankTransaction
{
    public function __construct(
        private ?int $tenantId,
        private int $bankAccountId,
        private string $description,
        private float $amount,
        private string $type,
        private \DateTimeInterface $transactionDate,
        private ?string $externalId = null,
        private ?float $balance = null,
        private string $status = 'imported',
        private ?int $matchedJournalEntryId = null,
        private ?int $categoryRuleId = null,
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

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getBankAccountId(): int
    {
        return $this->bankAccountId;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTransactionDate(): \DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function getMatchedJournalEntryId(): ?int
    {
        return $this->matchedJournalEntryId;
    }

    public function getCategoryRuleId(): ?int
    {
        return $this->categoryRuleId;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function categorize(int $categoryRuleId): void
    {
        $this->categoryRuleId = $categoryRuleId;
        $this->status = 'categorized';
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function matchToJournalEntry(int $journalEntryId): void
    {
        $this->matchedJournalEntryId = $journalEntryId;
        $this->status = 'reconciled';
        $this->updatedAt = new \DateTimeImmutable;
    }
}
