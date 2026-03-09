<?php

namespace App\Domain\Contracts;

interface StockMovementRepositoryInterface
{
    public function create(array $data): object;
    public function findById(string $id): ?object;
    public function findByIdAndTenant(string $id, string $tenantId): object;
    public function getByTenant(string $tenantId, array $params = []): mixed;
    public function getMovementsByProduct(string $tenantId, string $productId, array $params = []): mixed;
    public function getMovementsByWarehouse(string $tenantId, string $warehouseId, array $params = []): mixed;
    public function getMovementsByReference(string $tenantId, string $referenceId, string $referenceType): mixed;
}
