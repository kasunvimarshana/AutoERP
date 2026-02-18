<?php

declare(strict_types=1);

namespace Modules\POS\Repositories;

use Modules\POS\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository
{
    public function findById(string $id): ?Transaction
    {
        return Transaction::with(['lines', 'payments', 'location', 'cashRegister'])->find($id);
    }

    public function all(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Transaction::with(['lines', 'payments', 'location'])
            ->latest('transaction_date');

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['to_date']);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);
        return $transaction->fresh();
    }

    public function delete(Transaction $transaction): bool
    {
        return $transaction->delete();
    }
}
