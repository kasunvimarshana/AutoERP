<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Domain\Inventory\Models\Product;

/**
 * Inventory Service Interface
 */
interface InventoryServiceInterface
{
    public function createProduct(array $data): Product;
    public function updateProduct(string $id, array $data): Product;
    public function deleteProduct(string $id): bool;
    public function getProducts(string $tenantId, array $params = []): mixed;
    public function getProduct(string $id): Product;
}
