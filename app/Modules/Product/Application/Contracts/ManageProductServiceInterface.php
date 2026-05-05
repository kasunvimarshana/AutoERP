<?php declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Product\Domain\Entities\Product;

interface ManageProductServiceInterface
{
    /**
     * Create a new product
     */
    public function create(array $data): Product;

    /**
     * Find a product by ID
     */
    public function find(int $tenantId, string $id): Product;

    /**
     * List all products with optional filtering
     */
    public function list(int $tenantId, array $filters = []): array;

    /**
     * Update a product
     */
    public function update(int $tenantId, string $id, array $data): Product;

    /**
     * Delete a product
     */
    public function delete(int $tenantId, string $id): void;
}
