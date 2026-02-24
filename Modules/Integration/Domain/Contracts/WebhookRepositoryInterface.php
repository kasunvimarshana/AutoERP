<?php

namespace Modules\Integration\Domain\Contracts;

interface WebhookRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByTenant(string $tenantId): iterable;
    public function findActiveByTenant(string $tenantId): iterable;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): void;
}
