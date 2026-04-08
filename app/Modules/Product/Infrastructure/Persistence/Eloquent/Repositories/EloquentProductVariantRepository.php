<?php
declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;

class EloquentProductVariantRepository implements ProductVariantRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?ProductVariant
    {
        $model = ProductVariantModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByProduct(string $tenantId, string $productId): array
    {
        return ProductVariantModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('product_id', $productId)->get()
            ->map(fn(ProductVariantModel $m) => $this->mapToEntity($m))->all();
    }

    public function findBySku(string $tenantId, string $sku): ?ProductVariant
    {
        $model = ProductVariantModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('sku', $sku)->first();
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function save(ProductVariant $variant): void
    {
        /** @var ProductVariantModel $model */
        $model = ProductVariantModel::withoutGlobalScopes()->findOrNew($variant->id);
        $model->fill([
            'tenant_id'      => $variant->tenantId,
            'product_id'     => $variant->productId,
            'name'           => $variant->name,
            'sku'            => $variant->sku,
            'barcode'        => $variant->barcode,
            'attributes'     => $variant->attributes,
            'cost_price'     => $variant->costPrice,
            'sale_price'     => $variant->salePrice,
            'stock_quantity' => $variant->stockQuantity,
            'is_active'      => $variant->isActive,
        ]);
        if (!$model->exists) {
            $model->id = $variant->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        ProductVariantModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->find($id)?->delete();
    }

    private function mapToEntity(ProductVariantModel $model): ProductVariant
    {
        return new ProductVariant(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            productId: (string) $model->product_id,
            name: (string) $model->name,
            sku: (string) $model->sku,
            barcode: $model->barcode !== null ? (string) $model->barcode : null,
            attributes: (array) ($model->attributes ?? []),
            costPrice: (float) $model->cost_price,
            salePrice: (float) $model->sale_price,
            stockQuantity: (float) $model->stock_quantity,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
