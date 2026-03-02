<?php

declare(strict_types=1);

namespace Modules\Accounting\Domain\Contracts;

use Modules\Accounting\Domain\Entities\JournalEntry;

interface JournalEntryRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?JournalEntry;

    /**
     * Paginated list of journal entries with their lines.
     *
     * @return array{data: JournalEntry[], total: int, per_page: int, current_page: int, last_page: int}
     */
    public function findPaginated(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function save(JournalEntry $entry): JournalEntry;
}
