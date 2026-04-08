<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface WarehouseRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code, int $tenantId): mixed;

    public function findActiveByTenant(int $tenantId): Collection;
}
