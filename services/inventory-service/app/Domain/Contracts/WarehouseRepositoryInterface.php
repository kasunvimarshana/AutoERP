<?php

namespace App\Domain\Contracts;

interface WarehouseRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?object;
    public function findByCode(string $tenantId, string $code): ?object;
    public function list(string $tenantId, array $params = []): mixed;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
    public function getActiveWarehouses(string $tenantId): mixed;
    public function existsByCode(string $tenantId, string $code, ?string $excludeId = null): bool;
}
