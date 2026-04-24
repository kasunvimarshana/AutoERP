<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\ComboItem;

interface ComboItemRepositoryInterface extends RepositoryInterface
{
    public function save(ComboItem $comboItem): ComboItem;

    public function find(int|string $id, array $columns = ['*']): ?ComboItem;
}
