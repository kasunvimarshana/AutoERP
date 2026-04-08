<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Financial\Domain\Contracts\Repositories\BankTransactionRepositoryInterface;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\BankTransactionModel;

class EloquentBankTransactionRepository extends EloquentRepository implements BankTransactionRepositoryInterface
{
    public function __construct(BankTransactionModel $model)
    {
        parent::__construct($model);
    }

    /**
     * Return all transactions for a given bank account, newest first.
     */
    public function findByBankAccount(string $bankAccountId): Collection
    {
        return $this->model->newQuery()
            ->where('bank_account_id', $bankAccountId)
            ->orderByDesc('transaction_date')
            ->get();
    }

    /**
     * Return transactions filtered by status for a given bank account.
     */
    public function findByStatus(string $bankAccountId, string $status): Collection
    {
        return $this->model->newQuery()
            ->where('bank_account_id', $bankAccountId)
            ->where('status', $status)
            ->orderByDesc('transaction_date')
            ->get();
    }

    /**
     * Return unreconciled (pending/matched) transactions for a bank account within an optional date range.
     */
    public function findUnreconciled(
        string $bankAccountId,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): Collection {
        $query = $this->model->newQuery()
            ->where('bank_account_id', $bankAccountId)
            ->whereIn('status', ['pending', 'matched']);

        if ($fromDate !== null) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }

        return $query->orderBy('transaction_date')->get();
    }
}
