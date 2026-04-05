<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\ValueObjects\ProductType;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductModel;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function __construct(private readonly ProductModel $model) {}

    private function toEntity(ProductModel $m): Product
    {
        return new Product(
            $m->id, $m->tenant_id, $m->category_id, $m->name, $m->slug, $m->sku,
            new ProductType($m->type), $m->description, $m->status,
            (float)$m->base_price, (float)$m->tax_rate, $m->weight ? (float)$m->weight : null,
            $m->unit ?? 'each',
            (bool)$m->is_trackable, (bool)$m->is_serialized, (bool)$m->is_batch_tracked,
            $m->min_stock_level ? (float)$m->min_stock_level : null,
            $m->reorder_point ? (float)$m->reorder_point : null,
            $m->metadata, $m->created_at, $m->updated_at
        );
    }

    public function findById(int $id): ?Product
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findBySku(int $tenantId, string $sku): ?Product
    {
        $m = $this->model->newQuery()->where('tenant_id',$tenantId)->where('sku',$sku)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $q = $this->model->newQuery()->where('tenant_id', $tenantId);
        if (!empty($filters['category_id'])) $q->where('category_id', $filters['category_id']);
        if (!empty($filters['type'])) $q->where('type', $filters['type']);
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        return $q->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function create(array $data): Product
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?Product
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }
}
