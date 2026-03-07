<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service contract for product catalogue management.
 *
 * Products are tenant-scoped catalogue entries.
 * Inventory items track their stock levels.
 */
interface ProductServiceInterface
{
    /**
     * List all products for a tenant.
     *
     * @return LengthAwarePaginator<Product>
     */
    public function list(string $tenantId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new product in the catalogue.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(string $tenantId, array $data): Product;

    /**
     * Find a product by ID within a tenant scope.
     */
    public function find(string $id, string $tenantId): ?Product;

    /**
     * Update product details.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product;

    /**
     * Remove a product from the catalogue.
     */
    public function delete(Product $product): void;
}
