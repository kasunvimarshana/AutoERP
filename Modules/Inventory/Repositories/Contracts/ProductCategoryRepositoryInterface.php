<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Inventory\Entities\ProductCategory;

/**
 * Product Category Repository Interface
 *
 * Defines the contract for product category data access operations.
 */
interface ProductCategoryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find category by code.
     */
    public function findByCode(string $code): ?ProductCategory;

    /**
     * Get root categories (no parent).
     */
    public function getRootCategories(): Collection;

    /**
     * Get categories by parent.
     */
    public function getByParent(int $parentId): Collection;

    /**
     * Get active categories.
     */
    public function getActiveCategories(): Collection;

    /**
     * Get category tree.
     */
    public function getCategoryTree(): Collection;
}
