<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Illuminate\Support\Facades\DB;
use Modules\Product\Domain\Contracts\ProductAttributeRepositoryInterface;
use Modules\Product\Domain\Entities\ProductAttribute;
use Modules\Product\Infrastructure\Models\ProductAttributeModel;

class ProductAttributeRepository extends BaseRepository implements ProductAttributeRepositoryInterface
{
    protected function model(): string
    {
        return ProductAttributeModel::class;
    }

    public function findByProduct(int $productId, int $tenantId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ProductAttributeModel $m) => $this->toDomain($m))
            ->all();
    }

    public function save(ProductAttribute $attribute): ProductAttribute
    {
        if ($attribute->id !== null) {
            $model = $this->newQuery()
                ->where('id', $attribute->id)
                ->where('tenant_id', $attribute->tenantId)
                ->firstOrFail();
        } else {
            $model = new ProductAttributeModel;
        }

        $model->product_id = $attribute->productId;
        $model->tenant_id = $attribute->tenantId;
        $model->attribute_key = $attribute->attributeKey;
        $model->attribute_label = $attribute->attributeLabel;
        $model->attribute_value = $attribute->attributeValue;
        $model->attribute_type = $attribute->attributeType;
        $model->sort_order = $attribute->sortOrder;
        $model->save();

        return $this->toDomain($model);
    }

    public function replaceForProduct(int $productId, int $tenantId, array $attributes): void
    {
        DB::transaction(function () use ($productId, $tenantId, $attributes): void {
            $this->newQuery()
                ->where('product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->delete();

            foreach ($attributes as $attribute) {
                $model = new ProductAttributeModel;
                $model->product_id = $productId;
                $model->tenant_id = $tenantId;
                $model->attribute_key = $attribute->attributeKey;
                $model->attribute_label = $attribute->attributeLabel;
                $model->attribute_value = $attribute->attributeValue;
                $model->attribute_type = $attribute->attributeType;
                $model->sort_order = $attribute->sortOrder;
                $model->save();
            }
        });
    }

    public function delete(int $attributeId, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('id', $attributeId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Product attribute with ID {$attributeId} not found.");
        }

        $model->delete();
    }

    private function toDomain(ProductAttributeModel $model): ProductAttribute
    {
        return new ProductAttribute(
            id: $model->id,
            productId: $model->product_id,
            tenantId: $model->tenant_id,
            attributeKey: $model->attribute_key,
            attributeLabel: $model->attribute_label,
            attributeValue: $model->attribute_value,
            attributeType: $model->attribute_type,
            sortOrder: $model->sort_order,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
