<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Entities;

/**
 * Journal entry line (debit/credit split) domain entity.
 */
class JournalEntryLine
{
    public function __construct(
        public readonly string $id,
        public readonly string $journalEntryId,
        public readonly string $accountId,
        public readonly ?string $description,
        public readonly float $debit,
        public readonly float $credit,
        public readonly string $currencyCode,
    ) {}
}
