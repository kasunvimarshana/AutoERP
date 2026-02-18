<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Journal Entry Line Repository Interface
 *
 * Defines the contract for journal entry line data access operations.
 */
interface JournalEntryLineRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get lines by journal entry.
     */
    public function getByJournalEntry(int $journalEntryId): Collection;

    /**
     * Get lines by account.
     */
    public function getByAccount(int $accountId): Collection;

    /**
     * Get debit lines by journal entry.
     */
    public function getDebitLines(int $journalEntryId): Collection;

    /**
     * Get credit lines by journal entry.
     */
    public function getCreditLines(int $journalEntryId): Collection;
}
