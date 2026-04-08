<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use DateTimeInterface;

class JournalEntryLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $journalEntryId,
        public readonly string $accountId,
        public readonly string $type,
        public readonly float $amount,
        public readonly string $currencyCode,
        public readonly ?string $description,
        public readonly int $sequence,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isDebit(): bool { return $this->type === 'debit'; }
    public function isCredit(): bool { return $this->type === 'credit'; }
}
