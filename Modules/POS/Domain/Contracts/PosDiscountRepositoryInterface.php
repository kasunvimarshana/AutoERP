<?php

namespace Modules\POS\Domain\Contracts;

interface PosDiscountRepositoryInterface
{
    public function findById(string $id): ?object;

    public function findByCode(string $tenantId, string $code): ?object;

    public function create(array $data): object;

    public function update(string $id, array $data): object;

    public function paginate(string $tenantId, array $filters = [], int $perPage = 20): object;

    public function delete(string $id): void;

    public function incrementUsage(string $id): void;
}
