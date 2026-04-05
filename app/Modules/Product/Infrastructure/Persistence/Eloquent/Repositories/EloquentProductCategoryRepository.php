<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductCategoryModel;

class EloquentProductCategoryRepository implements ProductCategoryRepositoryInterface
{
    public function __construct(private readonly ProductCategoryModel $model) {}

    private function toEntity(ProductCategoryModel $m): ProductCategory
    {
        return new ProductCategory($m->id, $m->tenant_id, $m->parent_id, $m->name, $m->slug,
            $m->description, (bool)$m->is_active, (int)$m->level, $m->created_at, $m->updated_at);
    }

    public function findById(int $id): ?ProductCategory
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findBySlug(int $tenantId, string $slug): ?ProductCategory
    {
        $m = $this->model->newQuery()->where('tenant_id',$tenantId)->where('slug',$slug)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()->where('tenant_id',$tenantId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function findByParent(int $tenantId, ?int $parentId): array
    {
        return $this->model->newQuery()->where('tenant_id',$tenantId)->where('parent_id',$parentId)
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): ProductCategory
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?ProductCategory
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

    public function buildTree(int $tenantId): array
    {
        $all = $this->model->newQuery()->with('children')
            ->where('tenant_id',$tenantId)->whereNull('parent_id')->get();
        return $this->buildTreeFromNodes($all);
    }

    private function buildTreeFromNodes($nodes): array
    {
        return $nodes->map(function ($node) {
            $e = $this->toEntity($node);
            return [
                'id'          => $e->getId(),
                'name'        => $e->getName(),
                'slug'        => $e->getSlug(),
                'level'       => $e->getLevel(),
                'is_active'   => $e->isActive(),
                'children'    => $this->buildTreeFromNodes($node->children),
            ];
        })->all();
    }
}
