<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\FinanceCore;

use Modules\Core\Application\Results\Result;

interface PostJournalToLedgerServiceInterface
{
    public function execute(int|string $journalEntryId, array $payload = []): Result;
}
