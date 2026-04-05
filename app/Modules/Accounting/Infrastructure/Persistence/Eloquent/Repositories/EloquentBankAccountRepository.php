<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Accounting\Domain\Entities\BankAccount;
use Modules\Accounting\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;

class EloquentBankAccountRepository implements BankAccountRepositoryInterface
{
    public function __construct(private readonly BankAccountModel $model) {}

    private function toEntity(BankAccountModel $m): BankAccount
    {
        return new BankAccount(
            $m->id, $m->tenant_id, $m->account_id, $m->name, $m->bank_name,
            $m->account_number, $m->account_type, $m->currency,
            (float)$m->current_balance, (bool)$m->is_active, $m->description,
            $m->last_synced_at, $m->created_at, $m->updated_at,
        );
    }

    public function findById(int $id): ?BankAccount
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): BankAccount
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?BankAccount
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }
}
