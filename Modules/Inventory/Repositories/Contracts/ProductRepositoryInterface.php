<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Inventory\Entities\Product;

/**
 * Product Repository Interface
 *
 * Defines the contract for product data access operations.
 */
interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find product by SKU.
     */
    public function findBySku(string $sku): ?Product;

    /**
     * Find product by barcode.
     */
    public function findByBarcode(string $barcode): ?Product;

    /**
     * Get active products.
     */
    public function getActiveProducts(): Collection;

    /**
     * Get products by category.
     */
    public function getByCategory(int $categoryId): Collection;

    /**
     * Search products by name or SKU.
     */
    public function search(string $query): Collection;

    /**
     * Get stockable products.
     */
    public function getStockableProducts(): Collection;

    /**
     * Get products needing reorder.
     */
    public function getProductsNeedingReorder(): Collection;
}
