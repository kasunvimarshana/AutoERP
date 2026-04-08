<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;
use Modules\Accounting\Domain\Entities\BankTransaction;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\BankTransactionModel;
class EloquentBankTransactionRepository implements BankTransactionRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?BankTransaction
    {
        $model = BankTransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }
    public function findByBankAccount(string $tenantId, string $bankAccountId, array $filters = []): array
    {
        $query = BankTransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('bank_account_id', $bankAccountId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['from_date'])) {
            $query->whereDate('date', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->whereDate('date', '<=', $filters['to_date']);
        }
        return $query->orderByDesc('date')
            ->get()
            ->map(fn(BankTransactionModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function findByReference(string $tenantId, string $bankAccountId, string $reference): ?BankTransaction
    {
        $model = BankTransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('bank_account_id', $bankAccountId)
            ->where('reference', $reference)
            ->first();
        return $model !== null ? $this->mapToEntity($model) : null;
    }
    public function save(BankTransaction $tx): void
    {
        /** @var BankTransactionModel $model */
        $model = BankTransactionModel::withoutGlobalScopes()->findOrNew($tx->id);
        $model->fill([
            'tenant_id'        => $tx->tenantId,
            'bank_account_id'  => $tx->bankAccountId,
            'date'             => $tx->date->format('Y-m-d'),
            'description'      => $tx->description,
            'amount'           => $tx->amount,
            'type'             => $tx->type,
            'status'           => $tx->status,
            'source'           => $tx->source,
            'account_id'       => $tx->accountId,
            'journal_entry_id' => $tx->journalEntryId,
            'reference'        => $tx->reference,
            'metadata'         => $tx->metadata,
        ]);
        if (! $model->exists) {
            $model->id = $tx->id;
        }
        $model->save();
    }
    public function delete(string $tenantId, string $id): void
    {
        BankTransactionModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }
    private function mapToEntity(BankTransactionModel $model): BankTransaction
    {
        return new BankTransaction(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            bankAccountId: (string) $model->bank_account_id,
            date: $model->date ?? now(),
            description: (string) $model->description,
            amount: (float) $model->amount,
            type: (string) $model->type,
            status: (string) $model->status,
            source: (string) $model->source,
            accountId: $model->account_id !== null ? (string) $model->account_id : null,
            journalEntryId: $model->journal_entry_id !== null ? (string) $model->journal_entry_id : null,
            reference: $model->reference !== null ? (string) $model->reference : null,
            metadata: $model->metadata,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
