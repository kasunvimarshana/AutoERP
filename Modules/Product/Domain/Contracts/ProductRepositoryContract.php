<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Contracts;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Product repository contract.
 */
interface ProductRepositoryContract extends RepositoryContract
{
    /**
     * Find a product by its SKU (tenant-scoped).
     */
    public function findBySku(string $sku): ?Model;
}
