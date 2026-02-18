<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Accounting\Repositories\Contracts\JournalEntryRepositoryInterface;
use Modules\Core\Repositories\BaseRepository;

/**
 * Journal Entry Repository Implementation
 *
 * Handles all journal entry data access operations.
 */
class JournalEntryRepository extends BaseRepository implements JournalEntryRepositoryInterface
{
    /**
     * JournalEntryRepository constructor.
     */
    public function __construct(JournalEntry $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEntryNumber(string $entryNumber): ?JournalEntry
    {
        return $this->model->where('entry_number', $entryNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->orderBy('entry_date', 'desc')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByFiscalPeriod(int $fiscalPeriodId): Collection
    {
        return $this->model
            ->where('fiscal_period_id', $fiscalPeriodId)
            ->orderBy('entry_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        return $this->model
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('entry_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getUnbalancedEntries(): Collection
    {
        return $this->model
            ->whereRaw('total_debit != total_credit')
            ->orderBy('entry_date', 'desc')
            ->get();
    }
}
