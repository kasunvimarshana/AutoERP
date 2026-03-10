<?php

declare(strict_types=1);

namespace App\Domain\Product\Repositories;

use App\Domain\Shared\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * ProductRepositoryInterface
 *
 * Domain-specific product queries extending the base repository contract.
 */
interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search products by name, code, SKU, barcode, or category name.
     *
     * @param  string  $term
     * @param  string  $tenantId
     * @param  int     $perPage
     * @return LengthAwarePaginator
     */
    public function searchForTenant(string $term, string $tenantId, int $perPage = 15): LengthAwarePaginator;

    /**
     * List products for a tenant with optional filters and pagination.
     *
     * Supported filters include: category_id, status, price:gte, price:lte,
     * name:like, code:like, etc. (all base repository filter syntax applies).
     *
     * @param  string                $tenantId
     * @param  array<string, mixed>  $filters
     * @param  int                   $perPage
     * @param  array<string>         $relations
     * @param  array<string, string> $orderBy
     * @return LengthAwarePaginator
     */
    public function listForTenant(
        string $tenantId,
        array  $filters   = [],
        int    $perPage   = 15,
        array  $relations = ['category'],
        array  $orderBy   = ['created_at' => 'desc']
    ): LengthAwarePaginator;

    /**
     * Find a product by its unique code within a tenant.
     *
     * @param  string $code
     * @param  string $tenantId
     * @return \App\Infrastructure\Persistence\Models\Product|null
     */
    public function findByCode(string $code, string $tenantId): ?\App\Infrastructure\Persistence\Models\Product;

    /**
     * Find a product by its SKU within a tenant.
     *
     * @param  string $sku
     * @param  string $tenantId
     * @return \App\Infrastructure\Persistence\Models\Product|null
     */
    public function findBySku(string $sku, string $tenantId): ?\App\Infrastructure\Persistence\Models\Product;

    /**
     * Return products by category with optional nested category support.
     *
     * @param  string $categoryId
     * @param  string $tenantId
     * @param  int    $perPage
     * @return LengthAwarePaginator
     */
    public function listByCategory(string $categoryId, string $tenantId, int $perPage = 15): LengthAwarePaginator;
}
