<?php

namespace App\Repositories;

use App\DTOs\TenantDTO;
use App\Models\Tenant;
use App\Repositories\Interfaces\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TenantRepository implements TenantRepositoryInterface
{
    public function findById(int $id): ?Tenant
    {
        return Tenant::find($id);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return Tenant::bySlug($slug)->first();
    }

    public function findByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)->first();
    }

    public function paginate(
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $search = null
    ): LengthAwarePaginator {
        $query = Tenant::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%")
                  ->orWhere('domain', 'ilike', "%{$search}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['plan'])) {
            $query->where('plan', $filters['plan']);
        }

        $allowedSort = ['id', 'name', 'slug', 'plan', 'status', 'created_at', 'updated_at'];
        $sortBy  = in_array($sortBy, $allowedSort, true) ? $sortBy : 'created_at';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc'], true) ? $sortDir : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function create(TenantDTO $dto): Tenant
    {
        return Tenant::create($dto->toArray());
    }

    public function update(int $id, TenantDTO $dto): Tenant
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update($dto->toArray());

        return $tenant->fresh();
    }

    public function delete(int $id): bool
    {
        $tenant = Tenant::findOrFail($id);

        return (bool) $tenant->delete();
    }

    public function all(): Collection
    {
        return Tenant::all();
    }
}
