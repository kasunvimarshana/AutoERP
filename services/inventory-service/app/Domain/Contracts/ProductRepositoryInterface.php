<?php

namespace App\Domain\Contracts;

interface ProductRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?object;
    public function findBySku(string $tenantId, string $sku): ?object;
    public function findByCategory(string $tenantId, string $categoryId, array $params = []): mixed;
    public function findLowStock(string $tenantId, ?int $threshold = null): mixed;
    public function searchByNameOrSku(string $tenantId, string $query, array $params = []): mixed;
    public function list(string $tenantId, array $params = []): mixed;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
    public function existsBySku(string $tenantId, string $sku, ?string $excludeId = null): bool;
}
