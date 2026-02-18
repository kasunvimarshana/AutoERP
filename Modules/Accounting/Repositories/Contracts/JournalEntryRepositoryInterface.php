<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Journal Entry Repository Interface
 *
 * Defines the contract for journal entry data access operations.
 */
interface JournalEntryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find journal entry by entry number.
     */
    public function findByEntryNumber(string $entryNumber): ?JournalEntry;

    /**
     * Get entries by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get entries by fiscal period.
     */
    public function getByFiscalPeriod(int $fiscalPeriodId): Collection;

    /**
     * Get entries by date range.
     */
    public function getByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Get unbalanced entries.
     */
    public function getUnbalancedEntries(): Collection;
}
