<?php
namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Domain\Entities\Warehouse;

interface WarehouseRepositoryInterface
{
    public function findById(int $id): ?Warehouse;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findByCode(int $tenantId, string $code): ?Warehouse;
    public function create(array $data): Warehouse;
    public function update(Warehouse $warehouse, array $data): Warehouse;
    public function delete(Warehouse $warehouse): bool;
}
