<?php

namespace Modules\AssetManagement\Infrastructure\Repositories;

use Modules\AssetManagement\Domain\Contracts\AssetCategoryRepositoryInterface;
use Modules\AssetManagement\Infrastructure\Models\AssetCategoryModel;

class AssetCategoryRepository implements AssetCategoryRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return AssetCategoryModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return AssetCategoryModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return AssetCategoryModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = AssetCategoryModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        AssetCategoryModel::findOrFail($id)->delete();
    }
}
