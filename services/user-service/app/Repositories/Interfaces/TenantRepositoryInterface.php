<?php

namespace App\Repositories\Interfaces;

use App\DTOs\TenantDTO;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TenantRepositoryInterface
{
    public function findById(int $id): ?Tenant;

    public function findBySlug(string $slug): ?Tenant;

    public function findByDomain(string $domain): ?Tenant;

    /**
     * @return LengthAwarePaginator<Tenant>
     */
    public function paginate(
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $search = null
    ): LengthAwarePaginator;

    public function create(TenantDTO $dto): Tenant;

    public function update(int $id, TenantDTO $dto): Tenant;

    public function delete(int $id): bool;

    /**
     * @return Collection<int, Tenant>
     */
    public function all(): Collection;
}
