<?php

namespace Modules\Currency\Domain\Contracts;

interface ExchangeRateRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findLatest(string $tenantId, string $fromCode, string $toCode): ?object;
    public function findByTenant(string $tenantId, int $page = 1, int $perPage = 15): object;
    public function create(array $data): object;
    public function delete(string $id): void;
}
