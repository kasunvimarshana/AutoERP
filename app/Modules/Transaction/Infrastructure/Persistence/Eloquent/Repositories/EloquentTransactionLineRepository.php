<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Transaction\Domain\Entities\TransactionLine;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionLineRepositoryInterface;
use Modules\Transaction\Infrastructure\Persistence\Eloquent\Models\TransactionLineModel;

class EloquentTransactionLineRepository implements TransactionLineRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?TransactionLine
    {
        $model = TransactionLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByTransaction(string $tenantId, string $transactionId): array
    {
        return TransactionLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('transaction_id', $transactionId)
            ->get()
            ->map(fn (TransactionLineModel $model): TransactionLine => $this->mapToEntity($model))
            ->all();
    }

    public function save(TransactionLine $line): void
    {
        $model = TransactionLineModel::withoutGlobalScopes()->findOrNew($line->id);

        $model->fill([
            'tenant_id'      => $line->tenantId,
            'transaction_id' => $line->transactionId,
            'account_id'     => $line->accountId,
            'product_id'     => $line->productId,
            'quantity'       => $line->quantity,
            'unit_price'     => $line->unitPrice,
            'amount'         => $line->amount,
            'debit'          => $line->debit,
            'credit'         => $line->credit,
            'notes'          => $line->notes,
        ]);

        if (! $model->exists) {
            $model->id = $line->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        TransactionLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }

    private function mapToEntity(TransactionLineModel $model): TransactionLine
    {
        return new TransactionLine(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            transactionId: (string) $model->transaction_id,
            accountId: $model->account_id !== null ? (string) $model->account_id : null,
            productId: $model->product_id !== null ? (string) $model->product_id : null,
            quantity: (float) $model->quantity,
            unitPrice: (float) $model->unit_price,
            amount: (float) $model->amount,
            debit: (float) $model->debit,
            credit: (float) $model->credit,
            notes: $model->notes !== null ? (string) $model->notes : null,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
