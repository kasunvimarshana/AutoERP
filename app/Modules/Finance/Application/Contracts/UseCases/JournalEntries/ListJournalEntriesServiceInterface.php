<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts\UseCases\JournalEntries;

use Modules\Core\Application\Results\Result;

interface ListJournalEntriesServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}
