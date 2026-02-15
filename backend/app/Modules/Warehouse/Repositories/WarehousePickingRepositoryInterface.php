<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Repositories;

use App\Modules\Warehouse\Models\WarehousePicking;
use Illuminate\Database\Eloquent\Collection;

interface WarehousePickingRepositoryInterface
{
    public function findByNumber(string $pickingNumber): ?WarehousePicking;
    
    public function getByStatus(string $status): Collection;
    
    public function getByAssignee(int $userId): Collection;
    
    public function getPendingPickings(?int $branchId = null): Collection;
    
    public function getByType(string $type): Collection;
    
    public function getByDateRange(string $startDate, string $endDate): Collection;
    
    public function getWithItems(int $id): ?WarehousePicking;
    
    public function search(array $filters = []): Collection;
    
    public function getPickingEfficiency(int $userId, string $startDate, string $endDate): array;
}
