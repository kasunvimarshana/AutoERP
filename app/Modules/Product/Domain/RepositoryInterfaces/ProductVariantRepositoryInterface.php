<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Product\Domain\Entities\ProductVariant;

interface ProductVariantRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?ProductVariant;
    /** @return ProductVariant[] */
    public function findByProduct(string $tenantId, string $productId): array;
    public function findBySku(string $tenantId, string $sku): ?ProductVariant;
    public function save(ProductVariant $variant): void;
    public function delete(string $tenantId, string $id): void;
}
