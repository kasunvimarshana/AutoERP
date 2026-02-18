<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\JournalEntryLine;
use Modules\Accounting\Repositories\Contracts\JournalEntryLineRepositoryInterface;
use Modules\Core\Repositories\BaseRepository;

/**
 * Journal Entry Line Repository Implementation
 *
 * Handles all journal entry line data access operations.
 */
class JournalEntryLineRepository extends BaseRepository implements JournalEntryLineRepositoryInterface
{
    /**
     * JournalEntryLineRepository constructor.
     */
    public function __construct(JournalEntryLine $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getByJournalEntry(int $journalEntryId): Collection
    {
        return $this->model->where('journal_entry_id', $journalEntryId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByAccount(int $accountId): Collection
    {
        return $this->model->where('account_id', $accountId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getDebitLines(int $journalEntryId): Collection
    {
        return $this->model
            ->where('journal_entry_id', $journalEntryId)
            ->where('debit_amount', '>', 0)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getCreditLines(int $journalEntryId): Collection
    {
        return $this->model
            ->where('journal_entry_id', $journalEntryId)
            ->where('credit_amount', '>', 0)
            ->get();
    }
}
