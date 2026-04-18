<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductVariant;

interface ProductVariantRepositoryInterface extends RepositoryInterface
{
    public function save(ProductVariant $productVariant): ProductVariant;

    public function findByProductAndSku(int $productId, string $sku): ?ProductVariant;

    public function find($id, array $columns = ['*']): ?ProductVariant;
}
