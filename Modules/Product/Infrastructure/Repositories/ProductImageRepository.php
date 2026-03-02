<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Illuminate\Support\Facades\DB;
use Modules\Product\Domain\Contracts\ProductImageRepositoryInterface;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Infrastructure\Models\ProductImageModel;

class ProductImageRepository extends BaseRepository implements ProductImageRepositoryInterface
{
    protected function model(): string
    {
        return ProductImageModel::class;
    }

    public function findByProduct(int $productId, int $tenantId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ProductImageModel $m) => $this->toDomain($m))
            ->all();
    }

    public function save(ProductImage $image): ProductImage
    {
        if ($image->id !== null) {
            $model = $this->newQuery()
                ->where('id', $image->id)
                ->where('tenant_id', $image->tenantId)
                ->firstOrFail();
        } else {
            $model = new ProductImageModel;
        }

        $model->product_id = $image->productId;
        $model->tenant_id = $image->tenantId;
        $model->image_path = $image->imagePath;
        $model->image_source_type = $image->imageSourceType;
        $model->alt_text = $image->altText;
        $model->sort_order = $image->sortOrder;
        $model->is_primary = $image->isPrimary;
        $model->save();

        return $this->toDomain($model);
    }

    public function replaceForProduct(int $productId, int $tenantId, array $images): void
    {
        DB::transaction(function () use ($productId, $tenantId, $images): void {
            $this->newQuery()
                ->where('product_id', $productId)
                ->where('tenant_id', $tenantId)
                ->delete();

            foreach ($images as $image) {
                $model = new ProductImageModel;
                $model->product_id = $productId;
                $model->tenant_id = $tenantId;
                $model->image_path = $image->imagePath;
                $model->image_source_type = $image->imageSourceType;
                $model->alt_text = $image->altText;
                $model->sort_order = $image->sortOrder;
                $model->is_primary = $image->isPrimary;
                $model->save();
            }
        });
    }

    public function delete(int $imageId, int $tenantId): void
    {
        $model = $this->newQuery()
            ->where('id', $imageId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($model === null) {
            throw new \DomainException("Product image with ID {$imageId} not found.");
        }

        $model->delete();
    }

    private function toDomain(ProductImageModel $model): ProductImage
    {
        return new ProductImage(
            id: $model->id,
            productId: $model->product_id,
            tenantId: $model->tenant_id,
            imagePath: $model->image_path,
            altText: $model->alt_text,
            sortOrder: $model->sort_order,
            isPrimary: (bool) $model->is_primary,
            imageSourceType: $model->image_source_type ?? 'url',
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
