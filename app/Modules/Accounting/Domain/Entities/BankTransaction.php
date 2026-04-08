<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use DateTimeInterface;

class BankTransaction
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $bankAccountId,
        public readonly DateTimeInterface $date,
        public readonly string $description,
        public readonly float $amount,
        public readonly string $type,
        public readonly string $status,
        public readonly string $source,
        public readonly ?string $accountId,
        public readonly ?string $journalEntryId,
        public readonly ?string $reference,
        public readonly ?array $metadata,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isPending(): bool { return $this->status === 'pending'; }
    public function isCategorized(): bool { return $this->status === 'categorized'; }
    public function isReconciled(): bool { return $this->status === 'reconciled'; }

    public function categorize(string $accountId): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            bankAccountId: $this->bankAccountId,
            date: $this->date,
            description: $this->description,
            amount: $this->amount,
            type: $this->type,
            status: 'categorized',
            source: $this->source,
            accountId: $accountId,
            journalEntryId: $this->journalEntryId,
            reference: $this->reference,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }
}
