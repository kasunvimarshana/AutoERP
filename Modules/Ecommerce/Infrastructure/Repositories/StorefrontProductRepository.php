<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Modules\Ecommerce\Domain\Contracts\StorefrontProductRepositoryInterface;
use Modules\Ecommerce\Domain\Entities\StorefrontProduct;
use Modules\Ecommerce\Infrastructure\Models\StorefrontProductModel;

class StorefrontProductRepository extends BaseRepository implements StorefrontProductRepositoryInterface
{
    protected function model(): string
    {
        return StorefrontProductModel::class;
    }

    public function save(StorefrontProduct $product): StorefrontProduct
    {
        if ($product->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $product->tenantId)
                ->findOrFail($product->id);
        } else {
            $model = new StorefrontProductModel;
            $model->tenant_id = $product->tenantId;
            $model->product_id = $product->productId;
        }

        $model->slug = $product->slug;
        $model->name = $product->name;
        $model->description = $product->description;
        $model->price = $product->price;
        $model->currency = $product->currency;
        $model->is_active = $product->isActive;
        $model->is_featured = $product->isFeatured;
        $model->sort_order = $product->sortOrder;
        $model->save();

        return $this->toEntity($model);
    }

    public function findById(int $id, int $tenantId): ?StorefrontProduct
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findBySlug(string $slug, int $tenantId): ?StorefrontProduct
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, int $page, int $perPage): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (StorefrontProductModel $m) => $this->toEntity($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function findFeatured(int $tenantId): array
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_featured', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (StorefrontProductModel $m) => $this->toEntity($m))
            ->all();
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toEntity(StorefrontProductModel $model): StorefrontProduct
    {
        return new StorefrontProduct(
            id: $model->id,
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            slug: $model->slug,
            name: $model->name,
            description: $model->description,
            price: bcadd((string) $model->price, '0', 4),
            currency: $model->currency,
            isActive: (bool) $model->is_active,
            isFeatured: (bool) $model->is_featured,
            sortOrder: (int) $model->sort_order,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
