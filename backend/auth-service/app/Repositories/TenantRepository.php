<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Repositories\Interfaces\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TenantRepository implements TenantRepositoryInterface
{
    public function findById(int $id): ?Tenant
    {
        return Tenant::find($id);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)->first();
    }

    public function create(array $data): Tenant
    {
        return Tenant::create($data);
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $tenant->update($data);

        return $tenant->fresh();
    }

    public function delete(Tenant $tenant): bool
    {
        return $tenant->delete();
    }

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Tenant::query()
            ->when(isset($filters['search']), fn (Builder $q) => $q->where(fn (Builder $inner) => $inner->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('slug', 'like', "%{$filters['search']}%")
            )
            )
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', $filters['is_active'])
            )
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc');

        return $perPage !== null ? $query->paginate($perPage) : $query->get();
    }
}
