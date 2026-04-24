<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductAttribute;

interface ProductAttributeRepositoryInterface extends RepositoryInterface
{
    public function save(ProductAttribute $attribute): ProductAttribute;

    public function find(int|string $id, array $columns = ['*']): ?ProductAttribute;
}
