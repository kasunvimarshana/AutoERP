<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Repositories;
use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Domain\Entities\Account as AccountEntity;
use Modules\Accounting\Domain\Enums\AccountType;
use Modules\Accounting\Infrastructure\Models\Account as AccountModel;
class AccountRepository implements AccountRepositoryInterface {
    public function findById(int $id, int $tenantId): ?AccountEntity {
        $m = AccountModel::withoutGlobalScope('tenant')->where('id',$id)->where('tenant_id',$tenantId)->first();
        return $m ? $this->toDomain($m) : null;
    }
    public function findByCode(string $code, int $tenantId): ?AccountEntity {
        $m = AccountModel::withoutGlobalScope('tenant')->where('code',$code)->where('tenant_id',$tenantId)->first();
        return $m ? $this->toDomain($m) : null;
    }
    public function findAll(int $tenantId): array {
        return AccountModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderBy('code')
            ->get()
            ->map(fn(AccountModel $m): AccountEntity => $this->toDomain($m))
            ->all();
    }
    public function findActiveByTypes(array $types, int $tenantId): array {
        return AccountModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('type', $types)
            ->orderBy('type')
            ->orderBy('code')
            ->get()
            ->map(fn(AccountModel $m): AccountEntity => $this->toDomain($m))
            ->all();
    }
    public function save(AccountEntity $account): AccountEntity {
        $type = $account->getType();
        $m = AccountModel::withoutGlobalScope('tenant')->updateOrCreate(
            ['id' => $account->getId()],
            [
                'tenant_id'      => $account->getTenantId(),
                'parent_id'      => $account->getParentId(),
                'code'           => $account->getCode(),
                'name'           => $account->getName(),
                'type'           => $type->value,
                'normal_balance' => $type->normalBalance(),
                'is_active'      => $account->isActive(),
            ]
        );
        return $this->toDomain($m->fresh());
    }
    public function delete(int $id, int $tenantId): void {
        AccountModel::withoutGlobalScope('tenant')->where('id',$id)->where('tenant_id',$tenantId)->first()?->delete();
    }
    private function toDomain(AccountModel $m): AccountEntity {
        $type = $m->type instanceof AccountType ? $m->type : AccountType::from((string)$m->type);
        return new AccountEntity(
            id: (int)$m->id,
            tenantId: (int)$m->tenant_id,
            code: (string)$m->code,
            name: (string)$m->name,
            type: $type,
            parentId: $m->parent_id ? (int)$m->parent_id : null,
            isActive: (bool)$m->is_active,
            normalBalance: $type->normalBalance(),
            currentBalance: bcadd((string)($m->current_balance ?? '0'), '0', 4),
        );
    }
}
