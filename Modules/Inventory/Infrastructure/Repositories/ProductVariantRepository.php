<?php

namespace Modules\Inventory\Infrastructure\Repositories;

use Modules\Inventory\Domain\Contracts\ProductVariantRepositoryInterface;
use Modules\Inventory\Infrastructure\Models\ProductVariantModel;

class ProductVariantRepository implements ProductVariantRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ProductVariantModel::find($id);
    }

    public function findBySku(string $tenantId, string $sku): ?object
    {
        return ProductVariantModel::where('tenant_id', $tenantId)
            ->where('sku', $sku)
            ->first();
    }

    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object
    {
        $query = ProductVariantModel::where('tenant_id', $tenantId);

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                  ->orWhere('sku', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): object
    {
        return ProductVariantModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $variant = ProductVariantModel::findOrFail($id);
        $variant->update($data);
        return $variant->fresh();
    }

    public function delete(string $id): bool
    {
        return (bool) ProductVariantModel::findOrFail($id)->delete();
    }
}
