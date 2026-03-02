<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Repositories;

use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\Warehouse\Domain\Contracts\WarehouseRepositoryContract;
use Modules\Warehouse\Domain\Entities\WarehouseZone;

/**
 * Warehouse repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class WarehouseRepository extends AbstractRepository implements WarehouseRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = WarehouseZone::class;
    }
}
