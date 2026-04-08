<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Product\Domain\Entities\Product;

interface ProductServiceInterface
{
    public function getProduct(string $tenantId, string $id): Product;
    public function createProduct(string $tenantId, array $data): Product;
    public function updateProduct(string $tenantId, string $id, array $data): Product;
    public function deleteProduct(string $tenantId, string $id): void;
    /** @return Product[] */
    public function getAllProducts(string $tenantId): array;
    /** @return Product[] */
    public function getProductsByCategory(string $tenantId, string $categoryId): array;
    /** @return Product[] */
    public function getProductsByType(string $tenantId, string $type): array;
}
