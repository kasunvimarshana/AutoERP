<?php
declare(strict_types=1);
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductVariantModel;

class EloquentProductVariantRepository implements ProductVariantRepositoryInterface
{
    public function __construct(private readonly ProductVariantModel $model) {}

    private function toEntity(ProductVariantModel $m): ProductVariant
    {
        return new ProductVariant(
            $m->id, $m->tenant_id, $m->product_id,
            $m->sku, $m->attributes ?? [],
            $m->price_override, $m->cost_override,
            $m->status ?? 'active',
            $m->created_at, $m->updated_at,
        );
    }

    public function findById(int $id): ?ProductVariant
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByProduct(int $tenantId, int $productId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): ProductVariant
    {
        $m = $this->model->newQuery()->create($data);
        return $this->findById($m->id);
    }

    public function update(int $id, array $data): ?ProductVariant
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }
}
