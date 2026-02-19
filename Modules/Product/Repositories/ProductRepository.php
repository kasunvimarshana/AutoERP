<?php

declare(strict_types=1);

namespace Modules\Product\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Product\Enums\ProductType;
use Modules\Product\Models\Product;

/**
 * Product Repository
 *
 * Handles data access operations for Product model with specialized
 * methods for searching, filtering by type, category, and unit management
 */
class ProductRepository extends BaseRepository
{
    /**
     * Make a new Product model instance.
     */
    protected function makeModel(): Model
    {
        return new Product;
    }

    /**
     * Find products by category.
     */
    public function findByCategory(string $categoryId, bool $includeInactive = false): Collection
    {
        $query = $this->model->where('category_id', $categoryId);

        if (! $includeInactive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Find products by type.
     */
    public function findByType(ProductType $type, bool $includeInactive = false): Collection
    {
        $query = $this->model->ofType($type);

        if (! $includeInactive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Search products with advanced filtering.
     */
    public function searchProducts(
        ?string $searchTerm = null,
        ?ProductType $type = null,
        ?string $categoryId = null,
        bool $onlyActive = true,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->query();

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('code', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        if ($type) {
            $query->ofType($type);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($onlyActive) {
            $query->active();
        }

        return $query->with(['category', 'buyingUnit', 'sellingUnit'])->paginate($perPage);
    }

    /**
     * Get active products with relationships.
     */
    public function getActiveWithRelations(array $relations = []): Collection
    {
        $defaultRelations = ['category', 'buyingUnit', 'sellingUnit'];
        $relations = ! empty($relations) ? $relations : $defaultRelations;

        return $this->model->active()->with($relations)->get();
    }

    /**
     * Find products by code.
     */
    public function findByCode(string $code): ?Model
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get products with specific buying unit.
     */
    public function findByBuyingUnit(string $unitId): Collection
    {
        return $this->model->where('buying_unit_id', $unitId)->get();
    }

    /**
     * Get products with specific selling unit.
     */
    public function findBySellingUnit(string $unitId): Collection
    {
        return $this->model->where('selling_unit_id', $unitId)->get();
    }

    /**
     * Get bundle products with their items.
     */
    public function getBundlesWithItems(bool $onlyActive = true): Collection
    {
        $query = $this->model->ofType(ProductType::BUNDLE)
            ->with(['bundleItems.product']);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get composite products with their parts.
     */
    public function getCompositesWithParts(bool $onlyActive = true): Collection
    {
        $query = $this->model->ofType(ProductType::COMPOSITE)
            ->with(['compositeParts.component']);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get products with unit conversions.
     */
    public function getWithUnitConversions(string $productId): ?Model
    {
        return $this->model->with([
            'unitConversions.fromUnit',
            'unitConversions.toUnit',
        ])->find($productId);
    }

    /**
     * Get products by metadata field.
     */
    public function findByMetadata(string $key, mixed $value): Collection
    {
        return $this->model->where("metadata->{$key}", $value)->get();
    }

    /**
     * Get products in multiple categories.
     */
    public function findInCategories(array $categoryIds, bool $onlyActive = true): Collection
    {
        $query = $this->model->whereIn('category_id', $categoryIds);

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Count products by type.
     */
    public function countByType(ProductType $type, bool $onlyActive = true): int
    {
        $query = $this->model->ofType($type);

        if ($onlyActive) {
            $query->active();
        }

        return $query->count();
    }

    /**
     * Count products by category.
     */
    public function countByCategory(string $categoryId, bool $onlyActive = true): int
    {
        $query = $this->model->where('category_id', $categoryId);

        if ($onlyActive) {
            $query->active();
        }

        return $query->count();
    }

    /**
     * Toggle product active status.
     */
    public function toggleActive(string $id): bool
    {
        $product = $this->findOrFail($id);
        $product->is_active = ! $product->is_active;

        return $product->save();
    }

    /**
     * Bulk activate products.
     */
    public function bulkActivate(array $productIds): int
    {
        return $this->model->whereIn('id', $productIds)->update(['is_active' => true]);
    }

    /**
     * Bulk deactivate products.
     */
    public function bulkDeactivate(array $productIds): int
    {
        return $this->model->whereIn('id', $productIds)->update(['is_active' => false]);
    }
}
