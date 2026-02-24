<?php

namespace Modules\Expense\Infrastructure\Repositories;

use Modules\Expense\Domain\Contracts\ExpenseCategoryRepositoryInterface;
use Modules\Expense\Infrastructure\Models\ExpenseCategoryModel;

class ExpenseCategoryRepository implements ExpenseCategoryRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ExpenseCategoryModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return ExpenseCategoryModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return ExpenseCategoryModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = ExpenseCategoryModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        ExpenseCategoryModel::findOrFail($id)->delete();
    }
}
