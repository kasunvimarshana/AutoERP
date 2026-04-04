<?php
namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\InventoryCycleCount;

interface InventoryCycleCountRepositoryInterface
{
    public function findById(int $id): ?InventoryCycleCount;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): InventoryCycleCount;
    public function update(InventoryCycleCount $count, array $data): InventoryCycleCount;
    public function save(InventoryCycleCount $count): InventoryCycleCount;
}
