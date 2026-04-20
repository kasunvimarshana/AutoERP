<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductVariant;

interface ProductVariantRepositoryInterface extends RepositoryInterface
{
    public function save(ProductVariant $productVariant): ProductVariant;

    public function findByProductAndSku(int $productId, string $sku, ?int $tenantId = null): ?ProductVariant;

    public function clearDefaultForProduct(int $tenantId, int $productId, ?int $exceptVariantId = null): void;

    public function find(int|string $id, array $columns = ['*']): ?ProductVariant;
}
