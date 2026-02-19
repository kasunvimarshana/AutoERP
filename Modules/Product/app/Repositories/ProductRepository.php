<?php

declare(strict_types=1);

namespace Modules\Product\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Models\Product;

/**
 * Product Repository
 *
 * Handles data access for Product model
 * Extends BaseRepository for common CRUD operations
 */
class ProductRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new Product;
    }

    /**
     * Find product by SKU
     */
    public function findBySKU(string $sku): ?Product
    {
        /** @var Product|null */
        return $this->findOneBy(['sku' => $sku]);
    }

    /**
     * Find product by barcode
     */
    public function findByBarcode(string $barcode): ?Product
    {
        /** @var Product|null */
        return $this->findOneBy(['barcode' => $barcode]);
    }

    /**
     * Check if SKU exists
     */
    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('sku', $sku);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if barcode exists
     */
    public function barcodeExists(string $barcode, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('barcode', $barcode);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get active products
     */
    public function getActive(): Collection
    {
        return $this->model->newQuery()->where('status', 'active')->get();
    }

    /**
     * Get products by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->newQuery()->where('type', $type)->get();
    }

    /**
     * Get products by category
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->model->newQuery()->where('category_id', $categoryId)->get();
    }

    /**
     * Get low stock products
     */
    public function getLowStock(): Collection
    {
        return $this->model->newQuery()
            ->where('track_inventory', true)
            ->whereRaw('current_stock <= reorder_level')
            ->get();
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStock(): Collection
    {
        return $this->model->newQuery()
            ->where('track_inventory', true)
            ->where('current_stock', '<=', 0)
            ->get();
    }

    /**
     * Get featured products
     */
    public function getFeatured(): Collection
    {
        return $this->model->newQuery()->where('is_featured', true)->get();
    }

    /**
     * Search products by name, SKU, or barcode
     */
    public function search(string $query): Collection
    {
        return $this->model->newQuery()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('manufacturer', 'like', "%{$query}%")
                    ->orWhere('brand', 'like', "%{$query}%");
            })
            ->get();
    }

    /**
     * Get products with variants
     */
    public function getAllWithVariants(): Collection
    {
        return $this->model->newQuery()->with('variants')->get();
    }

    /**
     * Get product with variants by ID
     */
    public function findWithVariants(int $id): ?Product
    {
        /** @var Product|null */
        return $this->model->newQuery()->with('variants')->find($id);
    }

    /**
     * Get product with all relationships
     */
    public function findWithRelations(int $id): ?Product
    {
        /** @var Product|null */
        return $this->model->newQuery()
            ->with(['category', 'buyUnit', 'sellUnit', 'variants'])
            ->find($id);
    }

    /**
     * Update stock level
     */
    public function updateStock(int $id, int $quantity): bool
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->update(['current_stock' => $quantity]);
    }

    /**
     * Increment stock
     */
    public function incrementStock(int $id, int $quantity): bool
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->increment('current_stock', $quantity);
    }

    /**
     * Decrement stock
     */
    public function decrementStock(int $id, int $quantity): bool
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->decrement('current_stock', $quantity);
    }

    /**
     * Get products by manufacturer
     */
    public function getByManufacturer(string $manufacturer): Collection
    {
        return $this->model->newQuery()
            ->where('manufacturer', $manufacturer)
            ->get();
    }

    /**
     * Get products by brand
     */
    public function getByBrand(string $brand): Collection
    {
        return $this->model->newQuery()
            ->where('brand', $brand)
            ->get();
    }

    /**
     * Get products for branch
     */
    public function getForBranch(int $branchId): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->get();
    }
}
