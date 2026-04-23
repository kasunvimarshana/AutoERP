<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\Attribute;

interface AttributeRepositoryInterface extends RepositoryInterface
{
    public function save(Attribute $attribute): Attribute;

    public function find(int|string $id, array $columns = ['*']): ?Attribute;
}
