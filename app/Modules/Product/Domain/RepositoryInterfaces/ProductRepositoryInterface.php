<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Product\Domain\Entities\Product;

interface ProductRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Product;
    public function findBySku(string $tenantId, string $sku): ?Product;
    /** @return Product[] */
    public function findAll(string $tenantId): array;
    /** @return Product[] */
    public function findByCategory(string $tenantId, string $categoryId): array;
    /** @return Product[] */
    public function findByType(string $tenantId, string $type): array;
    public function save(Product $product): void;
    public function delete(string $tenantId, string $id): void;
}
