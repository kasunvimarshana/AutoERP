<?php

namespace App\Repositories\Interfaces;

use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TenantRepositoryInterface
{
    public function findById(int $id): ?Tenant;

    public function findBySlug(string $slug): ?Tenant;

    public function create(array $data): Tenant;

    public function update(Tenant $tenant, array $data): Tenant;

    public function delete(Tenant $tenant): bool;

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection;
}
