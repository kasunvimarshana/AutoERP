<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

final class JournalEntryLine
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $journalEntryId,
        public readonly int $accountId,
        public readonly ?string $accountCode,
        public readonly ?string $accountName,
        public readonly ?string $description,
        public readonly string $debitAmount,
        public readonly string $creditAmount,
        public readonly ?string $createdAt,
    ) {}
}
