<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Domain\Entities\Warehouse;

interface WarehouseRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?Warehouse;

    public function findByCode(string $code, int $tenantId): ?Warehouse;

    public function findAll(int $tenantId, int $page = 1, int $perPage = 25): array;

    public function save(Warehouse $warehouse): Warehouse;
}
