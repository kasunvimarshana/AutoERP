<?php
namespace Modules\Tenant\Domain\Contracts;
interface TenantRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findBySlug(string $slug): ?object;
    public function findByDomain(string $domain): ?object;
    public function paginate(array $filters = [], int $perPage = 15): object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function suspend(string $id): bool;
    public function activate(string $id): bool;
}
