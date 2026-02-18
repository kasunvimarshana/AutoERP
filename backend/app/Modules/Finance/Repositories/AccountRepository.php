<?php

namespace App\Modules\Finance\Repositories;

use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Models\Account;
use App\Repositories\BaseRepository;

/**
 * Account Repository
 *
 * Handles data access for accounts.
 */
class AccountRepository extends BaseRepository
{
    protected function model(): string
    {
        return Account::class;
    }

    public function findByCode(string $code): ?Account
    {
        return $this->model->where('code', $code)->first();
    }

    public function getByType(AccountType $type)
    {
        return $this->model
            ->byType($type)
            ->with(['currency', 'parent', 'children'])
            ->orderBy('code', 'asc')
            ->get();
    }

    public function getRootAccounts()
    {
        return $this->model
            ->rootAccounts()
            ->with(['currency', 'children'])
            ->orderBy('code', 'asc')
            ->get();
    }

    public function getActiveAccounts()
    {
        return $this->model
            ->active()
            ->with(['currency', 'parent'])
            ->orderBy('code', 'asc')
            ->get();
    }

    public function findWithRelations(int $id): Account
    {
        return $this->model
            ->with([
                'currency',
                'parent',
                'children',
                'journalEntryLines.journalEntry',
            ])
            ->findOrFail($id);
    }

    public function search(string $search)
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->with(['currency', 'parent'])
            ->orderBy('code', 'asc')
            ->get();
    }

    public function getAccountsForFinancialStatements(AccountType $type, string $startDate, string $endDate)
    {
        return $this->model
            ->byType($type)
            ->active()
            ->with([
                'journalEntryLines' => function ($query) use ($startDate, $endDate) {
                    $query->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                        $q->posted()
                            ->whereBetween('entry_date', [$startDate, $endDate]);
                    });
                },
            ])
            ->orderBy('code', 'asc')
            ->get();
    }
}
