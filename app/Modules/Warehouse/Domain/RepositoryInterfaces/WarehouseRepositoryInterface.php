<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Warehouse\Domain\Entities\Warehouse;

interface WarehouseRepositoryInterface extends RepositoryInterface
{
    public function save(Warehouse $warehouse): Warehouse;

    public function findByTenantAndCode(int $tenantId, string $code): ?Warehouse;

    public function clearDefaultForTenant(int $tenantId, ?int $excludeWarehouseId = null): void;
}
