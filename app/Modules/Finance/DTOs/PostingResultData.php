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

final readonly class PostingResultData
{
    public function __construct(
        public int $journalId,
        public string $journalNumber,
        public string $status,
        public string $totalDebit,
        public string $totalCredit,
        public int $ledgerEntryCount = 0,
    ) {}
}
