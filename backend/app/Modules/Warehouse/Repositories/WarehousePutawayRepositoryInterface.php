<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Repositories;

use App\Modules\Warehouse\Models\WarehousePutaway;
use Illuminate\Database\Eloquent\Collection;

interface WarehousePutawayRepositoryInterface
{
    public function findByNumber(string $putawayNumber): ?WarehousePutaway;
    
    public function getByStatus(string $status): Collection;
    
    public function getByAssignee(int $userId): Collection;
    
    public function getPendingPutaways(?int $branchId = null): Collection;
    
    public function getByType(string $type): Collection;
    
    public function getByDateRange(string $startDate, string $endDate): Collection;
    
    public function getWithItems(int $id): ?WarehousePutaway;
    
    public function search(array $filters = []): Collection;
}
