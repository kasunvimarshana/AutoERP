<?php
declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Product\Domain\Entities\Category;
use Modules\Product\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\CategoryModel;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Category
    {
        $model = CategoryModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findBySlug(string $tenantId, string $slug): ?Category
    {
        $model = CategoryModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('slug', $slug)->first();
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return CategoryModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->get()
            ->map(fn(CategoryModel $m) => $this->mapToEntity($m))->all();
    }

    public function findChildren(string $tenantId, ?string $parentId): array
    {
        return CategoryModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('parent_id', $parentId)->get()
            ->map(fn(CategoryModel $m) => $this->mapToEntity($m))->all();
    }

    public function findActive(string $tenantId): array
    {
        return CategoryModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->where('is_active', true)->get()
            ->map(fn(CategoryModel $m) => $this->mapToEntity($m))->all();
    }

    public function save(Category $category): void
    {
        /** @var CategoryModel $model */
        $model = CategoryModel::withoutGlobalScopes()->findOrNew($category->id);
        $model->fill([
            'tenant_id'   => $category->tenantId,
            'parent_id'   => $category->parentId,
            'name'        => $category->name,
            'slug'        => $category->slug,
            'description' => $category->description,
            'path'        => $category->path,
            'level'       => $category->level,
            'is_active'   => $category->isActive,
        ]);
        if (!$model->exists) {
            $model->id = $category->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        CategoryModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)->find($id)?->delete();
    }

    private function mapToEntity(CategoryModel $model): Category
    {
        return new Category(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            parentId: $model->parent_id !== null ? (string) $model->parent_id : null,
            name: (string) $model->name,
            slug: (string) $model->slug,
            description: $model->description !== null ? (string) $model->description : null,
            path: (string) $model->path,
            level: (int) $model->level,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
