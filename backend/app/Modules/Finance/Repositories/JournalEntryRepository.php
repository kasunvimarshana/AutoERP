<?php

namespace App\Modules\Finance\Repositories;

use App\Modules\Finance\Enums\JournalEntryStatus;
use App\Modules\Finance\Models\JournalEntry;
use App\Repositories\BaseRepository;

/**
 * Journal Entry Repository
 *
 * Handles data access for journal entries.
 */
class JournalEntryRepository extends BaseRepository
{
    protected function model(): string
    {
        return JournalEntry::class;
    }

    public function findByEntryNumber(string $entryNumber): ?JournalEntry
    {
        return $this->model->where('entry_number', $entryNumber)->first();
    }

    public function getByStatus(JournalEntryStatus $status)
    {
        return $this->model
            ->byStatus($status)
            ->with(['lines.account', 'lines.costCenter', 'poster'])
            ->orderBy('entry_date', 'desc')
            ->get();
    }

    public function getDraftEntries()
    {
        return $this->model
            ->draft()
            ->with(['lines.account', 'lines.costCenter'])
            ->orderBy('entry_date', 'desc')
            ->get();
    }

    public function getPostedEntries()
    {
        return $this->model
            ->posted()
            ->with(['lines.account', 'lines.costCenter', 'poster'])
            ->orderBy('entry_date', 'desc')
            ->get();
    }

    public function findWithRelations(int $id): JournalEntry
    {
        return $this->model
            ->with([
                'lines.account',
                'lines.costCenter',
                'poster',
                'reference',
            ])
            ->findOrFail($id);
    }

    public function getEntriesByDateRange(string $startDate, string $endDate)
    {
        return $this->model
            ->posted()
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->with(['lines.account', 'lines.costCenter'])
            ->orderBy('entry_date', 'asc')
            ->get();
    }

    public function getEntriesByAccount(int $accountId, string $startDate, string $endDate)
    {
        return $this->model
            ->posted()
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->whereHas('lines', function ($query) use ($accountId) {
                $query->where('account_id', $accountId);
            })
            ->with(['lines' => function ($query) use ($accountId) {
                $query->where('account_id', $accountId);
            }, 'lines.account'])
            ->orderBy('entry_date', 'asc')
            ->get();
    }

    public function search(string $search)
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->with(['lines.account'])
            ->orderBy('entry_date', 'desc')
            ->get();
    }
}
