<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\Serial;

interface SerialRepositoryInterface extends RepositoryInterface
{
    public function save(Serial $serial): Serial;

    public function find(int|string $id, array $columns = ['*']): ?Serial;
}
