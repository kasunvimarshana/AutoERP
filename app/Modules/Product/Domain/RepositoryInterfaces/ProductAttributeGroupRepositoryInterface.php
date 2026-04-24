<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductAttributeGroup;

interface ProductAttributeGroupRepositoryInterface extends RepositoryInterface
{
    public function save(ProductAttributeGroup $group): ProductAttributeGroup;

    public function find(int|string $id, array $columns = ['*']): ?ProductAttributeGroup;
}
