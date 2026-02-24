<?php

namespace Modules\Leave\Infrastructure\Repositories;

use Modules\Leave\Domain\Contracts\LeaveTypeRepositoryInterface;
use Modules\Leave\Infrastructure\Models\LeaveTypeModel;

class LeaveTypeRepository implements LeaveTypeRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return LeaveTypeModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return LeaveTypeModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return LeaveTypeModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = LeaveTypeModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        LeaveTypeModel::findOrFail($id)->delete();
    }
}
