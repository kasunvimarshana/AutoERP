<?php

namespace Modules\QualityControl\Infrastructure\Repositories;

use Modules\QualityControl\Domain\Contracts\InspectionRepositoryInterface;
use Modules\QualityControl\Infrastructure\Models\InspectionModel;

class InspectionRepository implements InspectionRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return InspectionModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return InspectionModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return InspectionModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = InspectionModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        InspectionModel::findOrFail($id)->delete();
    }
}
