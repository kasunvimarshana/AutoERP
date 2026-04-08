<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductImage;

interface ProductImageRepositoryInterface extends RepositoryInterface
{
    public function findByUuid(string $uuid): ?ProductImage;

    public function getByProduct(int $productId): Collection;

    public function save(ProductImage $image): ProductImage;
}
