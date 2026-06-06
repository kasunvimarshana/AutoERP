<?php

declare(strict_types=1);

namespace Modules\Core\DTOs\Integration;

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
