<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface JournalEntryRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a journal entry by its unique entry number.
     */
    public function findByEntryNumber(string $number): mixed;

    /**
     * Get journal entries whose entry_date falls within a date range.
     */
    public function findByDateRange(string $from, string $to): Collection;
}
