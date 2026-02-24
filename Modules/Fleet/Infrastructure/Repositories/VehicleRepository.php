<?php

namespace Modules\Fleet\Infrastructure\Repositories;

use Modules\Fleet\Domain\Contracts\VehicleRepositoryInterface;
use Modules\Fleet\Infrastructure\Models\VehicleModel;

class VehicleRepository implements VehicleRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return VehicleModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return VehicleModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return VehicleModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = VehicleModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        VehicleModel::findOrFail($id)->delete();
    }
}
