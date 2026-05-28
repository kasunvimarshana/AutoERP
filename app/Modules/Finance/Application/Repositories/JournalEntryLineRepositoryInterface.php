<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Repositories\Contracts\RepositoryPortInterface;

interface JournalEntryLineRepositoryInterface extends RepositoryPortInterface
{
    /** @return list<DataRecord> */
    public function listByJournalEntry(int $journalEntryId): array;

    public function nextLineNumber(int $tenantId, int $journalEntryId): int;
}
