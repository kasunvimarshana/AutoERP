<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Contracts;

use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\ValueObjects\SKU;

interface ProductRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Product;

    public function findBySku(SKU $sku, int $tenantId): ?Product;

    /**
     * @return Product[]
     */
    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function skuExistsForTenant(SKU $sku, int $tenantId, ?int $excludeId = null): bool;

    public function save(Product $product): Product;

    public function delete(int $id, int $tenantId): void;
}
