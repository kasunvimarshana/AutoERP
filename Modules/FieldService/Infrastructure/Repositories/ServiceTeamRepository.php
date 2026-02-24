<?php

namespace Modules\FieldService\Infrastructure\Repositories;

use Modules\FieldService\Domain\Contracts\ServiceTeamRepositoryInterface;
use Modules\FieldService\Infrastructure\Models\ServiceTeamModel;

class ServiceTeamRepository implements ServiceTeamRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ServiceTeamModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return ServiceTeamModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return ServiceTeamModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = ServiceTeamModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        ServiceTeamModel::findOrFail($id)->delete();
    }
}
