<?php

namespace Modules\AssetManagement\Infrastructure\Repositories;

use Modules\AssetManagement\Domain\Contracts\AssetRepositoryInterface;
use Modules\AssetManagement\Infrastructure\Models\AssetModel;

class AssetRepository implements AssetRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return AssetModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return AssetModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return AssetModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = AssetModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        AssetModel::findOrFail($id)->delete();
    }
}
