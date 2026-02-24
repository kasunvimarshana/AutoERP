<?php

namespace Modules\Contracts\Infrastructure\Repositories;

use Modules\Contracts\Domain\Contracts\ContractRepositoryInterface;
use Modules\Contracts\Infrastructure\Models\ContractModel;

class ContractRepository implements ContractRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ContractModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return ContractModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return ContractModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = ContractModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        ContractModel::findOrFail($id)->delete();
    }
}
