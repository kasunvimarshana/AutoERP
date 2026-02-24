<?php

namespace Modules\Leave\Infrastructure\Repositories;

use Modules\Leave\Domain\Contracts\LeaveRequestRepositoryInterface;
use Modules\Leave\Infrastructure\Models\LeaveRequestModel;

class LeaveRequestRepository implements LeaveRequestRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return LeaveRequestModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return LeaveRequestModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return LeaveRequestModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = LeaveRequestModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        LeaveRequestModel::findOrFail($id)->delete();
    }
}
