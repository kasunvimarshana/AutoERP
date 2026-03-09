<?php

namespace App\Domain\Contracts;

use App\Domain\Models\Tenant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TenantRepositoryInterface
{
    public function find(string $id): ?Tenant;
    public function findOrFail(string $id): Tenant;
    public function findBySubdomain(string $subdomain): ?Tenant;
    public function findAll(array $params = []): LengthAwarePaginator|Collection;
    public function create(array $data): Tenant;
    public function update(string $id, array $data): Tenant;
    public function delete(string $id): bool;
    public function findActive(): Collection;
}
