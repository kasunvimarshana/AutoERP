<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Product\Domain\Contracts\ProductRepositoryContract;
use Modules\Product\Domain\Entities\Product;

/**
 * Product repository implementation.
 *
 * Tenant-aware data access for Product.
 * No business logic — data access only.
 */
class ProductRepository extends AbstractRepository implements ProductRepositoryContract
{
    protected string $modelClass = Product::class;

    /**
     * {@inheritdoc}
     */
    public function findBySku(string $sku): ?Model
    {
        return $this->query()
            ->where('sku', $sku)
            ->first();
    }
}
