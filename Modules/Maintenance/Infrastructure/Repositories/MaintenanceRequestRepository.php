<?php

namespace Modules\Maintenance\Infrastructure\Repositories;

use Modules\Maintenance\Domain\Contracts\MaintenanceRequestRepositoryInterface;
use Modules\Maintenance\Infrastructure\Models\MaintenanceRequestModel;

class MaintenanceRequestRepository implements MaintenanceRequestRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return MaintenanceRequestModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return MaintenanceRequestModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return MaintenanceRequestModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = MaintenanceRequestModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        MaintenanceRequestModel::findOrFail($id)->delete();
    }
}
