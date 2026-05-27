<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\FinanceCore;

use Modules\Core\Application\Results\Result;

interface GenerateJournalEntryFromEventServiceInterface
{
    public function execute(array $payload): Result;
}
