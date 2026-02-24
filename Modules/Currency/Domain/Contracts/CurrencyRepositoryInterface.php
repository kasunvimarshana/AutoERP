<?php

namespace Modules\Currency\Domain\Contracts;

interface CurrencyRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByCode(string $tenantId, string $code): ?object;
    public function findByTenant(string $tenantId, int $page = 1, int $perPage = 15): object;
    public function findActiveByTenant(string $tenantId): iterable;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): void;
}
