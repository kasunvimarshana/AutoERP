<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Modules\Accounting\Enums\JournalEntryStatus;
use Modules\Accounting\Exceptions\JournalEntryNotFoundException;
use Modules\Accounting\Models\JournalEntry;
use Modules\Core\Repositories\BaseRepository;

class JournalEntryRepository extends BaseRepository
{
    public function __construct(JournalEntry $model)
    {
        parent::__construct($model);
    }

    protected function getModelClass(): string
    {
        return JournalEntry::class;
    }

    protected function getNotFoundExceptionClass(): string
    {
        return JournalEntryNotFoundException::class;
    }

    public function findByEntryNumber(string $entryNumber): ?JournalEntry
    {
        return $this->model->where('entry_number', $entryNumber)->first();
    }

    public function findByEntryNumberOrFail(string $entryNumber): JournalEntry
    {
        $entry = $this->findByEntryNumber($entryNumber);

        if (! $entry) {
            throw new JournalEntryNotFoundException("Journal entry with number {$entryNumber} not found");
        }

        return $entry;
    }

    public function getByStatus(JournalEntryStatus $status, int $perPage = 15)
    {
        return $this->model->where('status', $status)->latest('entry_date')->paginate($perPage);
    }

    public function getByFiscalPeriod(string $fiscalPeriodId, int $perPage = 15)
    {
        return $this->model->where('fiscal_period_id', $fiscalPeriodId)->latest('entry_date')->paginate($perPage);
    }

    public function getByDateRange(string $startDate, string $endDate, int $perPage = 15)
    {
        return $this->model
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->latest('entry_date')
            ->paginate($perPage);
    }

    public function getBySource(string $sourceType, string $sourceId): ?JournalEntry
    {
        return $this->model
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();
    }

    public function getDraftEntries()
    {
        return $this->model->where('status', JournalEntryStatus::Draft)->get();
    }

    public function getPostedEntries()
    {
        return $this->model->where('status', JournalEntryStatus::Posted)->get();
    }

    public function getUnbalancedEntries()
    {
        return $this->model
            ->where('status', JournalEntryStatus::Draft)
            ->whereRaw('(SELECT SUM(debit) FROM journal_lines WHERE journal_entry_id = journal_entries.id) != (SELECT SUM(credit) FROM journal_lines WHERE journal_entry_id = journal_entries.id)')
            ->get();
    }
}
