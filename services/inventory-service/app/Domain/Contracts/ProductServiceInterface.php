<?php

namespace App\Domain\Contracts;

interface ProductServiceInterface
{
    public function list(string $tenantId, array $params = []): mixed;
    public function create(string $tenantId, array $data): object;
    public function findById(string $tenantId, string $id): object;
    public function update(string $tenantId, string $id, array $data): object;
    public function delete(string $tenantId, string $id): bool;
    public function search(string $tenantId, string $query, array $filters = []): mixed;
    public function getLowStockProducts(string $tenantId, ?int $threshold = null): mixed;
}
