<?php

namespace Modules\Integration\Domain\Contracts;

interface ApiKeyRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByTenant(string $tenantId): iterable;
    public function create(array $data): object;
    public function revoke(string $id): object;
}
