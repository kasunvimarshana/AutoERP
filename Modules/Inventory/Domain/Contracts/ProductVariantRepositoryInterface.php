<?php

namespace Modules\Inventory\Domain\Contracts;

interface ProductVariantRepositoryInterface
{
    public function findById(string $id): ?object;

    public function findBySku(string $tenantId, string $sku): ?object;

    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object;

    public function create(array $data): object;

    public function update(string $id, array $data): object;

    public function delete(string $id): bool;
}
