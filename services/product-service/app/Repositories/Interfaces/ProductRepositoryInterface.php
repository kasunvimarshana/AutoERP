<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Retrieve a paginated list of products with optional filters.
     *
     * @param  array<string, mixed> $filters
     */
    public function getAll(array $filters = []): LengthAwarePaginator;

    /**
     * Find a product by its primary key.
     */
    public function findById(int $id): ?Product;

    /**
     * Find a product by its SKU.
     */
    public function findBySku(string $sku): ?Product;

    /**
     * Create a new product.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): Product;

    /**
     * Update an existing product.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): ?Product;

    /**
     * Soft-delete a product.
     */
    public function delete(int $id): bool;

    /**
     * Full-text search across name, description, sku, and category.
     *
     * @return Collection<int, Product>
     */
    public function search(string $term, int $limit = 15): Collection;
}
