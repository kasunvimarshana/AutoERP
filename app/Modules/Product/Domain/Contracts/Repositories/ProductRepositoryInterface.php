<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Contracts\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface ProductRepositoryInterface extends RepositoryInterface
{
    public function findBySku(string $sku): mixed;
    public function findByType(string $type): \Illuminate\Support\Collection;

    /**
     * Find a product by its GS1 GTIN within a tenant.
     */
    public function findByGtin(int $tenantId, string $gtin): mixed;
}
