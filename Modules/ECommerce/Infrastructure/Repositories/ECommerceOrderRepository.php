<?php

namespace Modules\ECommerce\Infrastructure\Repositories;

use Modules\ECommerce\Domain\Contracts\ECommerceOrderRepositoryInterface;
use Modules\ECommerce\Infrastructure\Models\ECommerceOrderModel;

class ECommerceOrderRepository implements ECommerceOrderRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ECommerceOrderModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return ECommerceOrderModel::where('tenant_id', $tenantId)->get();
    }

    public function create(array $data): object
    {
        return ECommerceOrderModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = ECommerceOrderModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        ECommerceOrderModel::findOrFail($id)->delete();
    }
}
