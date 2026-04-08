<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Modules\Warehouse\Domain\Entities\Warehouse;

interface WarehouseRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Warehouse;

    /** @return Warehouse[] */
    public function findAll(string $tenantId): array;

    public function findByCode(string $tenantId, string $code): ?Warehouse;

    public function save(Warehouse $warehouse): void;

    public function delete(string $tenantId, string $id): void;
}
