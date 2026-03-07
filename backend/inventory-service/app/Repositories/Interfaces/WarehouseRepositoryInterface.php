<?php

namespace App\Repositories\Interfaces;

use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WarehouseRepositoryInterface
{
    public function findById(int $id): ?Warehouse;

    public function create(array $data): Warehouse;

    public function update(Warehouse $warehouse, array $data): Warehouse;

    public function delete(Warehouse $warehouse): bool;

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection;
}
