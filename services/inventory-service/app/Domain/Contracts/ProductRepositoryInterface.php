<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Entities\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Product Repository Interface
 */
interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find product by SKU within a tenant.
     */
    public function findBySku(string $sku, int|string $tenantId): ?Product;

    /**
     * Get all products for a tenant with optional filters.
     * Returns paginated when per_page is set, all results otherwise.
     */
    public function findByTenant(int|string $tenantId, array $filters = []): Collection|LengthAwarePaginator;

    /**
     * Get products that need reordering.
     */
    public function findLowStock(int|string $tenantId): Collection;

    /**
     * Update product stock quantity atomically.
     */
    public function adjustStock(int|string $productId, int $delta): Product;
}
