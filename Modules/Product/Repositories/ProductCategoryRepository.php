<?php

declare(strict_types=1);

namespace Modules\Product\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Repositories\BaseRepository;
use Modules\Product\Models\ProductCategory;

/**
 * ProductCategory Repository
 *
 * Handles data access operations for ProductCategory model with
 * specialized methods for hierarchical tree management
 */
class ProductCategoryRepository extends BaseRepository
{
    /**
     * Make a new ProductCategory model instance.
     */
    protected function makeModel(): Model
    {
        return new ProductCategory;
    }

    /**
     * Get all root categories (no parent).
     */
    public function getRootCategories(bool $onlyActive = true): Collection
    {
        $query = $this->model->whereNull('parent_id');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get children of a specific category.
     */
    public function getChildren(string $parentId, bool $onlyActive = true): Collection
    {
        $query = $this->model->where('parent_id', $parentId);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get full category tree.
     */
    public function getTree(bool $onlyActive = true): Collection
    {
        $query = $this->model->with(['children' => function ($q) use ($onlyActive) {
            if ($onlyActive) {
                $q->active();
            }
        }])->whereNull('parent_id');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get category with all ancestors.
     */
    public function getWithAncestors(string $categoryId): ?Model
    {
        $category = $this->find($categoryId);

        if (! $category) {
            return null;
        }

        $ancestors = collect();
        $current = $category;

        while ($current->parent_id) {
            $parent = $this->find($current->parent_id);
            if ($parent) {
                $ancestors->prepend($parent);
                $current = $parent;
            } else {
                break;
            }
        }

        $category->ancestors = $ancestors;

        return $category;
    }

    /**
     * Get category with all descendants.
     */
    public function getWithDescendants(string $categoryId, bool $onlyActive = true): ?Model
    {
        $category = $this->model->with(['children' => function ($q) use ($onlyActive) {
            if ($onlyActive) {
                $q->active();
            }
        }])->find($categoryId);

        if (! $category) {
            return null;
        }

        return $category;
    }

    /**
     * Get all descendant IDs of a category.
     */
    public function getDescendantIds(string $categoryId, bool $onlyActive = true): array
    {
        $category = $this->find($categoryId);

        if (! $category) {
            return [];
        }

        $descendants = [];
        $this->collectDescendantIds($category, $descendants, $onlyActive);

        return $descendants;
    }

    /**
     * Recursively collect descendant IDs.
     */
    protected function collectDescendantIds(Model $category, array &$descendants, bool $onlyActive): void
    {
        $query = $this->model->where('parent_id', $category->id);

        if ($onlyActive) {
            $query->active();
        }

        $children = $query->get();

        foreach ($children as $child) {
            $descendants[] = $child->id;
            $this->collectDescendantIds($child, $descendants, $onlyActive);
        }
    }

    /**
     * Get category depth level.
     */
    public function getDepth(string $categoryId): int
    {
        $category = $this->find($categoryId);

        if (! $category) {
            return 0;
        }

        $depth = 0;
        $current = $category;

        while ($current->parent_id) {
            $depth++;
            $parent = $this->find($current->parent_id);
            if ($parent) {
                $current = $parent;
            } else {
                break;
            }
        }

        return $depth;
    }

    /**
     * Check if category has children.
     */
    public function hasChildren(string $categoryId, bool $onlyActive = true): bool
    {
        $query = $this->model->where('parent_id', $categoryId);

        if ($onlyActive) {
            $query->active();
        }

        return $query->exists();
    }

    /**
     * Check if category has products.
     */
    public function hasProducts(string $categoryId): bool
    {
        $category = $this->find($categoryId);

        if (! $category) {
            return false;
        }

        return $category->products()->exists();
    }

    /**
     * Move category to new parent.
     */
    public function moveToParent(string $categoryId, ?string $newParentId): bool
    {
        if ($newParentId && $this->isDescendantOf($categoryId, $newParentId)) {
            return false;
        }

        $category = $this->findOrFail($categoryId);
        $category->parent_id = $newParentId;

        return $category->save();
    }

    /**
     * Check if category is descendant of another.
     */
    public function isDescendantOf(string $categoryId, string $ancestorId): bool
    {
        $category = $this->find($categoryId);

        if (! $category) {
            return false;
        }

        $current = $category;

        while ($current->parent_id) {
            if ($current->parent_id === $ancestorId) {
                return true;
            }

            $parent = $this->find($current->parent_id);
            if ($parent) {
                $current = $parent;
            } else {
                break;
            }
        }

        return false;
    }

    /**
     * Get category path (breadcrumb).
     */
    public function getPath(string $categoryId): array
    {
        $category = $this->getWithAncestors($categoryId);

        if (! $category) {
            return [];
        }

        $path = [];

        if (isset($category->ancestors)) {
            foreach ($category->ancestors as $ancestor) {
                $path[] = [
                    'id' => $ancestor->id,
                    'name' => $ancestor->name,
                    'code' => $ancestor->code,
                ];
            }
        }

        $path[] = [
            'id' => $category->id,
            'name' => $category->name,
            'code' => $category->code,
        ];

        return $path;
    }

    /**
     * Find categories by code.
     */
    public function findByCode(string $code): ?Model
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Search categories.
     */
    public function searchCategories(string $searchTerm, bool $onlyActive = true, int $perPage = 15)
    {
        $query = $this->model->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('code', 'like', "%{$searchTerm}%")
                ->orWhere('description', 'like', "%{$searchTerm}%");
        });

        if ($onlyActive) {
            $query->active();
        }

        return $query->with('parent')->paginate($perPage);
    }

    /**
     * Get categories with product counts.
     */
    public function getWithProductCounts(bool $onlyActive = true): Collection
    {
        $query = $this->model->withCount('products');

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Toggle category active status.
     */
    public function toggleActive(string $id): bool
    {
        $category = $this->findOrFail($id);
        $category->is_active = ! $category->is_active;

        return $category->save();
    }
}
