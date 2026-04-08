<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\AccountModel;
class EloquentAccountRepository implements AccountRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Account
    {
        $model = AccountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }
    public function findByCode(string $tenantId, string $code): ?Account
    {
        $model = AccountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        return $model !== null ? $this->mapToEntity($model) : null;
    }
    public function findAll(string $tenantId): array
    {
        return AccountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(AccountModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function findByType(string $tenantId, string $type): array
    {
        return AccountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->get()
            ->map(fn(AccountModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function findChildren(string $tenantId, string $parentId): array
    {
        return AccountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('parent_id', $parentId)
            ->get()
            ->map(fn(AccountModel $m) => $this->mapToEntity($m))
            ->all();
    }
    public function save(Account $account): void
    {
        /** @var AccountModel $model */
        $model = AccountModel::withoutGlobalScopes()->findOrNew($account->id);
        $model->fill([
            'tenant_id'         => $account->tenantId,
            'parent_id'         => $account->parentId,
            'code'              => $account->code,
            'name'              => $account->name,
            'type'              => $account->type,
            'sub_type'          => $account->subType,
            'normal_balance'    => $account->normalBalance,
            'currency_code'     => $account->currencyCode,
            'is_active'         => $account->isActive,
            'is_locked'         => $account->isLocked,
            'is_system_account' => $account->isSystemAccount,
            'description'       => $account->description,
            'path'              => $account->path,
            'level'             => $account->level,
        ]);
        if (! $model->exists) {
            $model->id = $account->id;
        }
        $model->save();
    }
    public function delete(string $tenantId, string $id): void
    {
        AccountModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }
    private function mapToEntity(AccountModel $model): Account
    {
        return new Account(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            parentId: $model->parent_id !== null ? (string) $model->parent_id : null,
            code: (string) $model->code,
            name: (string) $model->name,
            type: (string) $model->type,
            subType: (string) $model->sub_type,
            normalBalance: (string) $model->normal_balance,
            currencyCode: (string) $model->currency_code,
            isActive: (bool) $model->is_active,
            isLocked: (bool) $model->is_locked,
            isSystemAccount: (bool) $model->is_system_account,
            description: $model->description !== null ? (string) $model->description : null,
            path: (string) $model->path,
            level: (int) $model->level,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
