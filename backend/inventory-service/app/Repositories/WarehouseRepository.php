<?php

namespace App\Repositories;

use App\Models\Warehouse;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WarehouseRepository implements WarehouseRepositoryInterface
{
    public function findById(int $id): ?Warehouse
    {
        return Warehouse::find($id);
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);

        return $warehouse->fresh();
    }

    public function delete(Warehouse $warehouse): bool
    {
        return $warehouse->delete();
    }

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Warehouse::query()
            ->when(isset($filters['search']), fn (Builder $q) => $q->where(fn (Builder $inner) => $inner->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('code', 'like', "%{$filters['search']}%")
                ->orWhere('city', 'like', "%{$filters['search']}%")
            )
            )
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN))
            )
            ->orderBy($filters['sort_by'] ?? 'name', $filters['sort_dir'] ?? 'asc');

        return $perPage !== null ? $query->paginate($perPage) : $query->get();
    }
}
