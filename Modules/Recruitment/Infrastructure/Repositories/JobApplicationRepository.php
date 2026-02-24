<?php

namespace Modules\Recruitment\Infrastructure\Repositories;

use Modules\Recruitment\Domain\Contracts\JobApplicationRepositoryInterface;
use Modules\Recruitment\Infrastructure\Models\JobApplicationModel;

class JobApplicationRepository implements JobApplicationRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return JobApplicationModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return JobApplicationModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return JobApplicationModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = JobApplicationModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        JobApplicationModel::findOrFail($id)->delete();
    }
}
