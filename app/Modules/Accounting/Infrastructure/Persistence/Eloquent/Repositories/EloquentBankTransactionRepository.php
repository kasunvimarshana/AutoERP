<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Accounting\Domain\Entities\BankTransaction;
use Modules\Accounting\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\BankTransactionModel;

class EloquentBankTransactionRepository implements BankTransactionRepositoryInterface
{
    public function __construct(private readonly BankTransactionModel $model) {}

    private function toEntity(BankTransactionModel $m): BankTransaction
    {
        return new BankTransaction(
            $m->id, $m->tenant_id, $m->bank_account_id,
            $m->transaction_date, (float)$m->amount, $m->description,
            $m->type, $m->status, $m->expense_category_id, $m->account_id,
            $m->journal_entry_id, $m->reference, $m->source,
            (array)($m->metadata ?? []), $m->created_at, $m->updated_at,
        );
    }

    public function findById(int $id): ?BankTransaction
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByBankAccount(int $bankAccountId, int $perPage = 15, int $page = 1): array
    {
        return $this->model->newQuery()
            ->where('bank_account_id', $bankAccountId)
            ->orderByDesc('transaction_date')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function findPendingByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): BankTransaction
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function createBatch(array $records): int
    {
        if (empty($records)) return 0;
        $this->model->newQuery()->insert($records);
        return count($records);
    }

    public function update(int $id, array $data): ?BankTransaction
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function updateBatch(array $ids, array $data): int
    {
        if (empty($ids)) return 0;
        return $this->model->newQuery()->whereIn('id', $ids)->update($data);
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }
}
