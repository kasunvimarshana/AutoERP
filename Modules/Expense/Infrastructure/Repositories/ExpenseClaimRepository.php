<?php

namespace Modules\Expense\Infrastructure\Repositories;

use Modules\Expense\Domain\Contracts\ExpenseClaimRepositoryInterface;
use Modules\Expense\Infrastructure\Models\ExpenseClaimModel;

class ExpenseClaimRepository implements ExpenseClaimRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ExpenseClaimModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return ExpenseClaimModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return ExpenseClaimModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = ExpenseClaimModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        ExpenseClaimModel::findOrFail($id)->delete();
    }
}
