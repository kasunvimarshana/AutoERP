<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Contracts\Repositories;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface WarehouseRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code): mixed;
    public function findDefault(): mixed;
}
