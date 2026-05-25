<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\JournalEntries;

use Modules\Core\Application\Results\Result;

interface DeleteJournalEntryServiceInterface
{
    public function execute(int|string $id): Result;
}
