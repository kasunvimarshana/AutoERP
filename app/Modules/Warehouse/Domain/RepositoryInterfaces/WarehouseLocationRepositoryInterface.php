<?php
declare(strict_types=1);
namespace Modules\Warehouse\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
interface WarehouseLocationRepositoryInterface {
    public function findById(int $id): ?WarehouseLocation;
    public function findByWarehouse(int $warehouseId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByParent(int $warehouseId, ?int $parentId): array;
    public function create(array $data): WarehouseLocation;
    public function update(int $id, array $data): ?WarehouseLocation;
    public function delete(int $id): bool;
    public function buildTree(int $warehouseId): array;
}
