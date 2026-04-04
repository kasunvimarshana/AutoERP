<?php
namespace Modules\Accounting\Infrastructure\Persistence\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Repositories\AccountRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\AccountModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentAccountRepository extends EloquentRepository implements AccountRepositoryInterface
{
    public function __construct(AccountModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Account
    {
        $model = $this->model->find($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByCode(int $tenantId, string $code): ?Account
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): Account
    {
        $model = $this->model->create($data);
        return $this->toEntity($model);
    }

    public function update(Account $account, array $data): Account
    {
        $model = $this->model->findOrFail($account->id);
        $model->fill($data)->save();
        return $this->toEntity($model);
    }

    public function delete(Account $account): bool
    {
        $model = $this->model->findOrFail($account->id);
        return (bool) $model->delete();
    }

    private function toEntity(AccountModel $model): Account
    {
        return new Account(
            id:          $model->id,
            tenantId:    $model->tenant_id,
            code:        $model->code,
            name:        $model->name,
            type:        $model->type,
            parentId:    $model->parent_id,
            currency:    $model->currency,
            isActive:    (bool) $model->is_active,
            description: $model->description,
        );
    }
}
