<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeInterface;
use Modules\Transaction\Domain\Entities\Transaction;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Models\TransactionModel;

class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Transaction
    {
        $model = TransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return TransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn (TransactionModel $model): Transaction => $this->mapToEntity($model))
            ->all();
    }

    public function findByType(string $tenantId, string $type): array
    {
        return TransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->get()
            ->map(fn (TransactionModel $model): Transaction => $this->mapToEntity($model))
            ->all();
    }

    public function findByStatus(string $tenantId, string $status): array
    {
        return TransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->map(fn (TransactionModel $model): Transaction => $this->mapToEntity($model))
            ->all();
    }

    public function findByReference(string $tenantId, string $referenceType, string $referenceId): array
    {
        return TransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get()
            ->map(fn (TransactionModel $model): Transaction => $this->mapToEntity($model))
            ->all();
    }

    public function findByDateRange(string $tenantId, DateTimeInterface $from, DateTimeInterface $to): array
    {
        return TransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereDate('transaction_date', '>=', $from->format('Y-m-d'))
            ->whereDate('transaction_date', '<=', $to->format('Y-m-d'))
            ->get()
            ->map(fn (TransactionModel $model): Transaction => $this->mapToEntity($model))
            ->all();
    }

    public function save(Transaction $transaction): void
    {
        $model = TransactionModel::withoutGlobalScopes()->findOrNew($transaction->id);

        $model->fill([
            'tenant_id'        => $transaction->tenantId,
            'type'             => $transaction->type,
            'reference_type'   => $transaction->referenceType,
            'reference_id'     => $transaction->referenceId,
            'status'           => $transaction->status,
            'description'      => $transaction->description,
            'transaction_date' => $transaction->transactionDate->format('Y-m-d'),
            'total_amount'     => $transaction->totalAmount,
        ]);

        if (! $model->exists) {
            $model->id = $transaction->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        TransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }

    private function mapToEntity(TransactionModel $model): Transaction
    {
        return new Transaction(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            type: (string) $model->type,
            referenceType: $model->reference_type !== null ? (string) $model->reference_type : null,
            referenceId: $model->reference_id !== null ? (string) $model->reference_id : null,
            status: (string) $model->status,
            description: $model->description !== null ? (string) $model->description : null,
            transactionDate: $model->transaction_date ?? now(),
            totalAmount: (float) $model->total_amount,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
