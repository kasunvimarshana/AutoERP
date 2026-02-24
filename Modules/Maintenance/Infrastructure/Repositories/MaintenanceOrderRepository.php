<?php

namespace Modules\Maintenance\Infrastructure\Repositories;

use Modules\Maintenance\Domain\Contracts\MaintenanceOrderRepositoryInterface;
use Modules\Maintenance\Infrastructure\Models\MaintenanceOrderModel;

class MaintenanceOrderRepository implements MaintenanceOrderRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return MaintenanceOrderModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return MaintenanceOrderModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return MaintenanceOrderModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = MaintenanceOrderModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        MaintenanceOrderModel::findOrFail($id)->delete();
    }
}
