<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Finance\DTOs;

use Modules\Finance\Enums\JournalStatus;

final readonly class JournalPostingResult
{
    public function __construct(
        public int $journalEntryId,
        public string $journalNumber,
        public JournalStatus $status,
        public string $totalDebit,
        public string $totalCredit,
        public int $ledgerEntryCount,
    ) {}
}
