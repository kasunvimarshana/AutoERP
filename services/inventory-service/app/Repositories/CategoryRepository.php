<?php

namespace App\Repositories;

use App\Domain\Contracts\CategoryRepositoryInterface;
use App\Domain\Models\Category;
use Illuminate\Database\Eloquent\Builder;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    protected array $searchableFields = ['name', 'slug', 'description'];

    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    protected function getAllowedFilterFields(): array
    {
        return ['tenant_id', 'parent_id', 'name', 'slug', 'is_active'];
    }

    protected function getAllowedSortFields(): array
    {
        return ['name', 'slug', 'sort_order', 'created_at', 'updated_at'];
    }

    protected function getAllowedRelations(): array
    {
        return ['parent', 'children', 'products'];
    }

    // -------------------------------------------------------------------------

    public function findById(string $tenantId, string $id): ?object
    {
        return $this->model->byTenant($tenantId)->with(['parent'])->find($id);
    }

    public function findBySlug(string $tenantId, string $slug): ?object
    {
        return $this->model->byTenant($tenantId)->where('slug', $slug)->first();
    }

    public function list(string $tenantId, array $params = []): mixed
    {
        $params['filter']['tenant_id'] = $tenantId;
        return $this->query($params);
    }

    public function getTree(string $tenantId): array
    {
        $allCategories = $this->model
            ->byTenant($tenantId)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount('products')
            ->get();

        return $this->buildTree($allCategories);
    }

    private function buildTree(\Illuminate\Support\Collection $categories, ?string $parentId = null): array
    {
        return $categories
            ->filter(fn ($c) => $c->parent_id === $parentId)
            ->map(function ($category) use ($categories) {
                $category->setRelation('children', collect($this->buildTree($categories, $category->id)));
                return $category;
            })
            ->values()
            ->all();
    }

    public function getChildren(string $tenantId, string $parentId): mixed
    {
        return $this->model
            ->byTenant($tenantId)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get();
    }

    public function hasChildren(string $id): bool
    {
        return $this->model->where('parent_id', $id)->exists();
    }

    public function hasProducts(string $id): bool
    {
        return $this->model->find($id)?->products()->exists() ?? false;
    }

    protected function applyDefaultSort(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
