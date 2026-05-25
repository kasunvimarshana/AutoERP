<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\JournalEntryLines;

use Modules\Core\Application\Results\Result;

interface GetJournalEntryLineServiceInterface
{
    public function execute(int|string $id): Result;
}
