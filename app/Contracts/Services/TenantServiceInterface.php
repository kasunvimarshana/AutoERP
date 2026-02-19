<?php

namespace App\Contracts\Services;

use App\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;

interface TenantServiceInterface
{
    public function create(array $data): Tenant;

    public function update(string $id, array $data): Tenant;

    public function suspend(string $id): Tenant;

    public function activate(string $id): Tenant;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findBySlug(string $slug): ?Tenant;
}
