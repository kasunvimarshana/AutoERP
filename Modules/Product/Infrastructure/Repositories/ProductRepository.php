<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Repositories;

use Modules\Product\Domain\Contracts\ProductRepositoryInterface;
use Modules\Product\Domain\Entities\Product as ProductEntity;
use Modules\Product\Domain\Enums\ProductType;
use Modules\Product\Domain\ValueObjects\SKU;
use Modules\Product\Infrastructure\Models\Product as ProductModel;

class ProductRepository implements ProductRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?ProductEntity
    {
        $model = ProductModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySku(SKU $sku, int $tenantId): ?ProductEntity
    {
        $model = ProductModel::withoutGlobalScope('tenant')
            ->where('sku', $sku->getValue())
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array
    {
        return ProductModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (ProductModel $m): ProductEntity => $this->toDomain($m))
            ->all();
    }

    public function skuExistsForTenant(SKU $sku, int $tenantId, ?int $excludeId = null): bool
    {
        $query = ProductModel::withoutGlobalScope('tenant')
            ->where('sku', $sku->getValue())
            ->where('tenant_id', $tenantId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function save(ProductEntity $product): ProductEntity
    {
        $data = [
            'tenant_id'     => $product->getTenantId(),
            'name'          => $product->getName(),
            'sku'           => $product->getSku()->getValue(),
            'category_id'   => $product->getCategoryId(),
            'brand_id'      => $product->getBrandId(),
            'unit_id'       => $product->getUnitId(),
            'type'          => $product->getType()->value,
            'cost_price'    => $product->getCostPrice(),
            'selling_price' => $product->getSellingPrice(),
            'reorder_point' => $product->getReorderPoint(),
            'is_active'     => $product->isActive(),
            'description'   => $product->getDescription(),
        ];

        if ($product->getId() > 0) {
            $model = ProductModel::withoutGlobalScope('tenant')->findOrFail($product->getId());
            $model->update($data);
        } else {
            $model = ProductModel::create($data);
        }

        return $this->toDomain($model->fresh());
    }

    public function delete(int $id, int $tenantId): void
    {
        ProductModel::withoutGlobalScope('tenant')
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first()
            ?->delete();
    }

    private function toDomain(ProductModel $model): ProductEntity
    {
        return new ProductEntity(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            sku: new SKU((string) $model->sku),
            categoryId: $model->category_id ? (int) $model->category_id : null,
            brandId: $model->brand_id ? (int) $model->brand_id : null,
            unitId: $model->unit_id ? (int) $model->unit_id : null,
            type: $model->type instanceof ProductType ? $model->type : ProductType::from((string) $model->type),
            costPrice: (string) $model->cost_price,
            sellingPrice: (string) $model->selling_price,
            reorderPoint: (string) $model->reorder_point,
            isActive: (bool) $model->is_active,
            description: $model->description,
        );
    }
}
