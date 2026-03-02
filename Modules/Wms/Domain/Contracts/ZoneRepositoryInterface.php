<?php

declare(strict_types=1);

namespace Modules\Wms\Domain\Contracts;

use Modules\Wms\Domain\Entities\Zone;

interface ZoneRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Zone;

    public function findAll(int $tenantId, int $warehouseId, int $page, int $perPage): array;

    public function findByWarehouse(int $tenantId, int $warehouseId): array;

    public function save(Zone $zone): Zone;

    public function delete(int $id, int $tenantId): void;
}
