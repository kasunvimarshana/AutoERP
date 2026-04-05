<?php
declare(strict_types=1);
namespace Modules\StockMovement\Domain\RepositoryInterfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\StockMovement\Domain\Entities\StockMovement;
interface StockMovementRepositoryInterface {
    public function findById(int $id): ?StockMovement;
    public function findByProduct(int $tenantId, int $productId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByWarehouse(int $tenantId, int $warehouseId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): StockMovement;
}
