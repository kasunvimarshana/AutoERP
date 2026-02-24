<?php

namespace Modules\Maintenance\Infrastructure\Repositories;

use Modules\Maintenance\Domain\Contracts\EquipmentRepositoryInterface;
use Modules\Maintenance\Infrastructure\Models\EquipmentModel;

class EquipmentRepository implements EquipmentRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return EquipmentModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return EquipmentModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return EquipmentModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = EquipmentModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        EquipmentModel::findOrFail($id)->delete();
    }
}
