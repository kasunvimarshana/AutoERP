<?php

namespace Modules\ECommerce\Infrastructure\Repositories;

use Modules\ECommerce\Domain\Contracts\ProductListingRepositoryInterface;
use Modules\ECommerce\Infrastructure\Models\ProductListingModel;

class ProductListingRepository implements ProductListingRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ProductListingModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return ProductListingModel::where('tenant_id', $tenantId)->get();
    }

    public function findActive(string $tenantId): iterable
    {
        return ProductListingModel::where('tenant_id', $tenantId)
            ->where('is_published', true)
            ->get();
    }

    public function create(array $data): object
    {
        return ProductListingModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = ProductListingModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        ProductListingModel::findOrFail($id)->delete();
    }
}
