<?php

namespace Modules\Budget\Infrastructure\Repositories;

use Modules\Budget\Domain\Contracts\BudgetRepositoryInterface;
use Modules\Budget\Infrastructure\Models\BudgetModel;

class BudgetRepository implements BudgetRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return BudgetModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return BudgetModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return BudgetModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = BudgetModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        BudgetModel::findOrFail($id)->delete();
    }
}
