<?php

namespace Modules\Recruitment\Infrastructure\Repositories;

use Modules\Recruitment\Domain\Contracts\JobPositionRepositoryInterface;
use Modules\Recruitment\Infrastructure\Models\JobPositionModel;

class JobPositionRepository implements JobPositionRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return JobPositionModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return JobPositionModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return JobPositionModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = JobPositionModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        JobPositionModel::findOrFail($id)->delete();
    }
}
