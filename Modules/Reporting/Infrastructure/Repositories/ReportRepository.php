<?php

namespace Modules\Reporting\Infrastructure\Repositories;

use Modules\Reporting\Domain\Contracts\ReportRepositoryInterface;
use Modules\Reporting\Infrastructure\Models\ReportModel;

class ReportRepository implements ReportRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ReportModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return ReportModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return ReportModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = ReportModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        ReportModel::findOrFail($id)->delete();
    }
}
