<?php
namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Domain\Entities\WarehouseZone;

interface WarehouseZoneRepositoryInterface
{
    public function findById(int $id): ?WarehouseZone;
    public function findByWarehouse(int $warehouseId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): WarehouseZone;
    public function update(WarehouseZone $zone, array $data): WarehouseZone;
    public function delete(WarehouseZone $zone): bool;
}
