<?php

namespace App\Services;

use App\Enums\AccountingPeriodStatus;
use App\Enums\JournalEntryStatus;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function paginateAccounts(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ChartOfAccount::where('tenant_id', $tenantId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('code')->paginate($perPage);
    }

    public function createAccount(array $data): ChartOfAccount
    {
        return DB::transaction(function () use ($data) {
            return ChartOfAccount::create($data);
        });
    }

    public function updateAccount(string $id, array $data): ChartOfAccount
    {
        return DB::transaction(function () use ($id, $data) {
            $account = ChartOfAccount::findOrFail($id);

            if ($account->is_system) {
                throw new \RuntimeException('System accounts cannot be modified.');
            }

            $account->update($data);

            return $account->fresh();
        });
    }

    public function paginatePeriods(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return AccountingPeriod::where('tenant_id', $tenantId)
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);
    }

    public function createPeriod(array $data): AccountingPeriod
    {
        return AccountingPeriod::create($data);
    }

    public function closePeriod(string $id): AccountingPeriod
    {
        return DB::transaction(function () use ($id) {
            $period = AccountingPeriod::findOrFail($id);
            $period->update(['status' => AccountingPeriodStatus::Closed]);

            return $period->fresh();
        });
    }

    public function paginateJournalEntries(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = JournalEntry::where('tenant_id', $tenantId)->with('lines');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        return $query->orderBy('date', 'desc')->paginate($perPage);
    }

    public function createJournalEntry(array $data): JournalEntry
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $totalDebit = '0';
            $totalCredit = '0';

            foreach ($lines as $line) {
                $totalDebit = bcadd($totalDebit, (string) ($line['debit'] ?? '0'), 8);
                $totalCredit = bcadd($totalCredit, (string) ($line['credit'] ?? '0'), 8);
            }

            if (bccomp($totalDebit, $totalCredit, 8) !== 0) {
                throw new \InvalidArgumentException('Journal entry is not balanced: debits must equal credits.');
            }

            $entry = JournalEntry::create($data);

            foreach ($lines as $line) {
                $line['journal_entry_id'] = $entry->id;
                $line['tenant_id'] = $entry->tenant_id;
                $entry->lines()->create($line);
            }

            return $entry->fresh(['lines']);
        });
    }

    public function postJournalEntry(string $id, string $userId): JournalEntry
    {
        return DB::transaction(function () use ($id, $userId) {
            $entry = JournalEntry::lockForUpdate()->findOrFail($id);

            if ($entry->status !== JournalEntryStatus::Draft) {
                throw new \RuntimeException('Only draft journal entries can be posted.');
            }

            $entry->update([
                'status' => JournalEntryStatus::Posted,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            return $entry->fresh();
        });
    }
}
