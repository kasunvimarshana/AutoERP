<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Entities;

use Modules\Accounting\Domain\Enums\JournalEntryStatus;

final class JournalEntry
{
    /**
     * @param  JournalEntryLine[]  $lines
     */
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $entryNumber,
        public readonly string $entryDate,
        public readonly ?string $reference,
        public readonly ?string $description,
        public readonly string $currency,
        public readonly JournalEntryStatus $status,
        public readonly string $totalDebit,
        public readonly string $totalCredit,
        public readonly array $lines,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public function isBalanced(): bool
    {
        return bccomp($this->totalDebit, $this->totalCredit, 4) === 0;
    }
}
