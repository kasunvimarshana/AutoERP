<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\AttributeValue;

interface AttributeValueRepositoryInterface extends RepositoryInterface
{
    public function save(AttributeValue $attributeValue): AttributeValue;

    public function find(int|string $id, array $columns = ['*']): ?AttributeValue;
}
