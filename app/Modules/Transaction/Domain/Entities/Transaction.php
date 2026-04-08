<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;

class Transaction
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $type,
        public readonly ?string $referenceType,
        public readonly ?string $referenceId,
        public readonly string $status,
        public readonly ?string $description,
        public readonly DateTimeInterface $transactionDate,
        public readonly float $totalAmount,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function post(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            type: $this->type,
            referenceType: $this->referenceType,
            referenceId: $this->referenceId,
            status: 'posted',
            description: $this->description,
            transactionDate: $this->transactionDate,
            totalAmount: $this->totalAmount,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }

    public function void(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            type: $this->type,
            referenceType: $this->referenceType,
            referenceId: $this->referenceId,
            status: 'voided',
            description: $this->description,
            transactionDate: $this->transactionDate,
            totalAmount: $this->totalAmount,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }
}
