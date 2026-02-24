<?php

namespace Modules\Accounting\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\Accounting\Domain\Contracts\BankTransactionRepositoryInterface;
use Modules\Accounting\Infrastructure\Models\BankTransactionModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class BankTransactionRepository extends BaseEloquentRepository implements BankTransactionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new BankTransactionModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = BankTransactionModel::query();

        if (! empty($filters['bank_account_id'])) {
            $query->where('bank_account_id', $filters['bank_account_id']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['transaction_date'])) {
            $query->whereDate('transaction_date', $filters['transaction_date']);
        }

        return $query->orderBy('transaction_date', 'desc')->latest()->paginate($perPage);
    }

    public function findUnreconciledByAccount(string $bankAccountId): Collection
    {
        return BankTransactionModel::where('bank_account_id', $bankAccountId)
            ->where('status', 'unreconciled')
            ->orderBy('transaction_date')
            ->get();
    }
}
