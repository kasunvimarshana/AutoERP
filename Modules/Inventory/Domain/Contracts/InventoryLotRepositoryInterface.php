<?php

namespace Modules\Inventory\Domain\Contracts;

interface InventoryLotRepositoryInterface
{
    public function findById(string $id): ?object;

    public function findByLotNumber(string $tenantId, string $productId, string $lotNumber): ?object;

    public function create(array $data): object;

    public function update(string $id, array $data): object;

    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object;
}
