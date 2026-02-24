<?php

namespace Modules\Reporting\Infrastructure\Repositories;

use Modules\Reporting\Domain\Contracts\DashboardRepositoryInterface;
use Modules\Reporting\Infrastructure\Models\DashboardModel;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return DashboardModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return DashboardModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return DashboardModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = DashboardModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        DashboardModel::findOrFail($id)->delete();
    }
}
