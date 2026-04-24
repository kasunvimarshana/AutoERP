<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ProductAttributeValue;

interface ProductAttributeValueRepositoryInterface extends RepositoryInterface
{
    public function save(ProductAttributeValue $attributeValue): ProductAttributeValue;

    public function find(int|string $id, array $columns = ['*']): ?ProductAttributeValue;
}
