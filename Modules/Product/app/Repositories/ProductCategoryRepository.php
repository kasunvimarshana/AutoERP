<?php

declare(strict_types=1);

namespace Modules\Product\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\ProductCategory;

/**
 * Product Category Repository
 *
 * Handles data access for ProductCategory model
 */
class ProductCategoryRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new ProductCategory;
    }

    /**
     * Find category by code
     */
    public function findByCode(string $code): ?ProductCategory
    {
        /** @var ProductCategory|null */
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Check if code exists
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get active categories
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()->where('is_active', true)->get();
    }

    /**
     * Get root categories (no parent)
     */
    public function getRootCategories(): Collection
    {
        return $this->model->newQuery()->whereNull('parent_id')->get();
    }

    /**
     * Get child categories
     */
    public function getChildren(int $parentId): Collection
    {
        return $this->model->newQuery()->where('parent_id', $parentId)->get();
    }

    /**
     * Get category tree with children
     */
    public function getCategoryTree(): Collection
    {
        return $this->model->newQuery()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get category with products
     */
    public function findWithProducts(int $id): ?ProductCategory
    {
        /** @var ProductCategory|null */
        return $this->model->newQuery()->with('products')->find($id);
    }

    /**
     * Search categories
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->get();
    }
}
