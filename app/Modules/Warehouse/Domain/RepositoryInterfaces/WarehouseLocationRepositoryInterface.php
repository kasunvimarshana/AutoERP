<?php
namespace Modules\Warehouse\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;

interface WarehouseLocationRepositoryInterface
{
    public function findById(int $id): ?WarehouseLocation;
    public function findByBarcode(string $barcode): ?WarehouseLocation;
    public function findByZone(int $zoneId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): WarehouseLocation;
    public function update(WarehouseLocation $location, array $data): WarehouseLocation;
    public function delete(WarehouseLocation $location): bool;
}
