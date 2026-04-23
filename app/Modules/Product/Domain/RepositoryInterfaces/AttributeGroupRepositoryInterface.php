<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\AttributeGroup;

interface AttributeGroupRepositoryInterface extends RepositoryInterface
{
    public function save(AttributeGroup $attributeGroup): AttributeGroup;

    public function find(int|string $id, array $columns = ['*']): ?AttributeGroup;
}
