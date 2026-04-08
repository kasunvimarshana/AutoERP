<?php
declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Product
    {
        $model = ProductModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findBySku(string $tenantId, string $sku): ?Product
    {
        $model = ProductModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('sku', $sku)->first();
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return ProductModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->get()
            ->map(fn(ProductModel $m) => $this->mapToEntity($m))->all();
    }

    public function findByCategory(string $tenantId, string $categoryId): array
    {
        return ProductModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('category_id', $categoryId)->get()
            ->map(fn(ProductModel $m) => $this->mapToEntity($m))->all();
    }

    public function findByType(string $tenantId, string $type): array
    {
        return ProductModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('type', $type)->get()
            ->map(fn(ProductModel $m) => $this->mapToEntity($m))->all();
    }

    public function save(Product $product): void
    {
        /** @var ProductModel $model */
        $model = ProductModel::withoutGlobalScopes()->findOrNew($product->id);
        $model->fill([
            'tenant_id'         => $product->tenantId,
            'category_id'       => $product->categoryId,
            'name'              => $product->name,
            'sku'               => $product->sku,
            'barcode'           => $product->barcode,
            'type'              => $product->type,
            'status'            => $product->status,
            'description'       => $product->description,
            'short_description' => $product->shortDescription,
            'unit'              => $product->unit,
            'weight'            => $product->weight,
            'weight_unit'       => $product->weightUnit,
            'has_variants'      => $product->hasVariants,
            'is_trackable'      => $product->isTrackable,
            'is_serial_tracked' => $product->isSerialTracked,
            'is_batch_tracked'  => $product->isBatchTracked,
            'cost_price'        => $product->costPrice,
            'sale_price'        => $product->salePrice,
            'min_stock_level'   => $product->minStockLevel,
            'reorder_point'     => $product->reorderPoint,
            'tax_group_id'      => $product->taxGroupId,
            'image_url'         => $product->imageUrl,
            'metadata'          => $product->metadata,
        ]);
        if (!$model->exists) {
            $model->id = $product->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        ProductModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->find($id)?->delete();
    }

    private function mapToEntity(ProductModel $model): Product
    {
        return new Product(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            categoryId: $model->category_id !== null ? (string) $model->category_id : null,
            name: (string) $model->name,
            sku: (string) $model->sku,
            barcode: $model->barcode !== null ? (string) $model->barcode : null,
            type: (string) $model->type,
            status: (string) $model->status,
            description: $model->description !== null ? (string) $model->description : null,
            shortDescription: $model->short_description !== null ? (string) $model->short_description : null,
            unit: (string) $model->unit,
            weight: $model->weight !== null ? (float) $model->weight : null,
            weightUnit: $model->weight_unit !== null ? (string) $model->weight_unit : null,
            hasVariants: (bool) $model->has_variants,
            isTrackable: (bool) $model->is_trackable,
            isSerialTracked: (bool) $model->is_serial_tracked,
            isBatchTracked: (bool) $model->is_batch_tracked,
            costPrice: (float) $model->cost_price,
            salePrice: (float) $model->sale_price,
            minStockLevel: (float) $model->min_stock_level,
            reorderPoint: (float) $model->reorder_point,
            taxGroupId: $model->tax_group_id !== null ? (string) $model->tax_group_id : null,
            imageUrl: $model->image_url !== null ? (string) $model->image_url : null,
            metadata: (array) ($model->metadata ?? []),
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
