<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Entities;

/**
 * Journal entry header domain entity.
 */
class JournalEntry
{
    public function __construct(
        public readonly string $id,
        public readonly int $tenantId,
        public readonly string $entryNumber,
        public readonly string $entryDate,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $description,
        public readonly string $currencyCode,
        public readonly float $totalDebit,
        public readonly float $totalCredit,
    ) {}
}
