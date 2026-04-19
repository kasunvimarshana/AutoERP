<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\Account;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;

class EloquentAccountRepository extends EloquentRepository implements AccountRepositoryInterface
{
    public function __construct(AccountModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (AccountModel $model): Account => $this->mapModelToDomainEntity($model));
    }

    public function save(Account $account): Account
    {
        $data = [
            'tenant_id' => $account->getTenantId(),
            'parent_id' => $account->getParentId(),
            'code' => $account->getCode(),
            'name' => $account->getName(),
            'type' => $account->getType(),
            'sub_type' => $account->getSubType(),
            'normal_balance' => $account->getNormalBalance(),
            'is_system' => $account->isSystem(),
            'is_bank_account' => $account->isBankAccount(),
            'is_credit_card' => $account->isCreditCard(),
            'currency_id' => $account->getCurrencyId(),
            'description' => $account->getDescription(),
            'is_active' => $account->isActive(),
            'path' => $account->getPath(),
            'depth' => $account->getDepth(),
        ];

        if ($account->getId()) {
            $model = $this->update($account->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var AccountModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndCode(int $tenantId, string $code): ?Account
    {
        /** @var AccountModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?Account
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(AccountModel $model): Account
    {
        return new Account(
            tenantId: (int) $model->tenant_id,
            code: (string) $model->code,
            name: (string) $model->name,
            type: (string) $model->type,
            normalBalance: (string) $model->normal_balance,
            parentId: $model->parent_id !== null ? (int) $model->parent_id : null,
            subType: $model->sub_type,
            isSystem: (bool) $model->is_system,
            isBankAccount: (bool) $model->is_bank_account,
            isCreditCard: (bool) $model->is_credit_card,
            currencyId: $model->currency_id !== null ? (int) $model->currency_id : null,
            description: $model->description,
            isActive: (bool) $model->is_active,
            path: $model->path,
            depth: (int) ($model->depth ?? 0),
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
