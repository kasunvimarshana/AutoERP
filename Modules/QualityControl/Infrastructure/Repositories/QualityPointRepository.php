<?php

namespace Modules\QualityControl\Infrastructure\Repositories;

use Modules\QualityControl\Domain\Contracts\QualityPointRepositoryInterface;
use Modules\QualityControl\Infrastructure\Models\QualityPointModel;

class QualityPointRepository implements QualityPointRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return QualityPointModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return QualityPointModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return QualityPointModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = QualityPointModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        QualityPointModel::findOrFail($id)->delete();
    }
}
