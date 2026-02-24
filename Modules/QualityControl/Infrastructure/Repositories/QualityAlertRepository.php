<?php

namespace Modules\QualityControl\Infrastructure\Repositories;

use Modules\QualityControl\Domain\Contracts\QualityAlertRepositoryInterface;
use Modules\QualityControl\Infrastructure\Models\QualityAlertModel;

class QualityAlertRepository implements QualityAlertRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return QualityAlertModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return QualityAlertModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return QualityAlertModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = QualityAlertModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        QualityAlertModel::findOrFail($id)->delete();
    }
}
