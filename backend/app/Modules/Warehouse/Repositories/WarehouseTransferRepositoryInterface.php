<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Repositories;

use App\Modules\Warehouse\Models\WarehouseTransfer;
use Illuminate\Database\Eloquent\Collection;

interface WarehouseTransferRepositoryInterface
{
    public function findByNumber(string $transferNumber): ?WarehouseTransfer;
    
    public function getByStatus(string $status): Collection;
    
    public function getByBranch(int $branchId, string $direction = 'source'): Collection;
    
    public function getPendingTransfers(?int $branchId = null): Collection;
    
    public function getInTransitTransfers(?int $branchId = null): Collection;
    
    public function getByDateRange(string $startDate, string $endDate): Collection;
    
    public function getWithItems(int $id): ?WarehouseTransfer;
    
    public function search(array $filters = []): Collection;
}
