<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface ProductVariantRepositoryInterface extends RepositoryInterface
{
    public function findByProduct(string $productId): Collection;
    public function findBySku(string $sku): mixed;
}
