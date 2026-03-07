<?php

namespace App\Repositories\Interfaces;

use App\Models\Warehouse;
use Illuminate\Pagination\LengthAwarePaginator;

interface WarehouseRepositoryInterface
{
    public function allForTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id, int $tenantId): ?Warehouse;

    public function findByCode(string $code, int $tenantId): ?Warehouse;

    public function create(array $data): Warehouse;

    public function update(Warehouse $warehouse, array $data): Warehouse;

    public function delete(Warehouse $warehouse): bool;

    public function getActiveForTenant(int $tenantId): \Illuminate\Database\Eloquent\Collection;
}
