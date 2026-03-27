<?php

declare(strict_types=1);

namespace Modules\Account\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Account\Domain\Entities\Account;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Account\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentAccountRepository extends EloquentRepository implements AccountRepositoryInterface
{
    public function __construct(AccountModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (AccountModel $model): Account => $this->mapModelToDomainEntity($model));
    }

    public function findByCode(int $tenantId, string $code): ?Account
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenant(int $tenantId): Collection
    {
        return $this->toDomainCollection($this->model->where('tenant_id', $tenantId)->get());
    }

    public function findByType(int $tenantId, string $type): Collection
    {
        return $this->toDomainCollection(
            $this->model->where('tenant_id', $tenantId)->where('type', $type)->get()
        );
    }

    public function save(Account $account): Account
    {
        $data = [
            'tenant_id'   => $account->getTenantId(),
            'code'        => $account->getCode(),
            'name'        => $account->getName(),
            'type'        => $account->getType(),
            'subtype'     => $account->getSubtype(),
            'description' => $account->getDescription(),
            'currency'    => $account->getCurrency(),
            'balance'     => $account->getBalance(),
            'is_system'   => $account->isSystem(),
            'parent_id'   => $account->getParentId(),
            'status'      => $account->getStatus(),
            'attributes'  => $account->getAttributes(),
            'metadata'    => $account->getMetadata(),
        ];

        if ($account->getId()) {
            $model = $this->update($account->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    private function mapModelToDomainEntity(AccountModel $model): Account
    {
        return new Account(
            tenantId: $model->tenant_id,
            code: $model->code,
            name: $model->name,
            type: $model->type,
            subtype: $model->subtype,
            description: $model->description,
            currency: $model->currency,
            balance: (float) $model->balance,
            isSystem: (bool) $model->is_system,
            parentId: $model->parent_id,
            status: $model->status,
            attributes: $model->attributes,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
