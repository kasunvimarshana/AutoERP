<?php
declare(strict_types=1);
namespace Modules\Warehouse\Application\Contracts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
interface WarehouseLocationServiceInterface {
    public function findById(int $id): WarehouseLocation;
    public function findByWarehouse(int $warehouseId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function getTree(int $warehouseId): array;
    public function create(array $data): WarehouseLocation;
    public function update(int $id, array $data): WarehouseLocation;
    public function delete(int $id): bool;
}
