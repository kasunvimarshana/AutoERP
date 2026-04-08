<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface ProductVariantRepositoryInterface extends RepositoryInterface
{
    public function findByProduct(int $productId): Collection;

    public function findBySku(string $sku, int $tenantId): mixed;

    public function findByBarcode(string $barcode, int $tenantId): mixed;
}
