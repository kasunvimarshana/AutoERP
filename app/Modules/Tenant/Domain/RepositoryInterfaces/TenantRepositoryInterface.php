<?php
namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Modules\Tenant\Domain\Entities\Tenant;

interface TenantRepositoryInterface
{
    public function findById(int $id): ?Tenant;
    public function findBySlug(string $slug): ?Tenant;
    public function findAll(array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator;
    public function create(array $data): Tenant;
    public function update(Tenant $tenant, array $data): Tenant;
    public function delete(Tenant $tenant): bool;
}
