<?php

namespace Modules\FieldService\Infrastructure\Repositories;

use Modules\FieldService\Domain\Contracts\ServiceOrderRepositoryInterface;
use Modules\FieldService\Infrastructure\Models\ServiceOrderModel;

class ServiceOrderRepository implements ServiceOrderRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ServiceOrderModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return ServiceOrderModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return ServiceOrderModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = ServiceOrderModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        ServiceOrderModel::findOrFail($id)->delete();
    }
}
