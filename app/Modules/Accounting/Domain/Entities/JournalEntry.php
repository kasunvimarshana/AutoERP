<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;

class JournalEntry
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $number,
        public readonly DateTimeInterface $date,
        public readonly string $description,
        public readonly ?string $reference,
        public readonly string $status,
        public readonly ?string $sourceType,
        public readonly ?string $sourceId,
        public readonly ?DateTimeInterface $postedAt,
        public readonly ?DateTimeInterface $voidedAt,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isPosted(): bool { return $this->status === 'posted'; }
    public function isVoided(): bool { return $this->status === 'voided'; }

    public function post(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            number: $this->number,
            date: $this->date,
            description: $this->description,
            reference: $this->reference,
            status: 'posted',
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            postedAt: new DateTimeImmutable(),
            voidedAt: $this->voidedAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }

    public function void(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            number: $this->number,
            date: $this->date,
            description: $this->description,
            reference: $this->reference,
            status: 'voided',
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            postedAt: $this->postedAt,
            voidedAt: new DateTimeImmutable(),
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }
}
