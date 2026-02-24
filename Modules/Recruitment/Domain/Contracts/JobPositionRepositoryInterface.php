<?php

namespace Modules\Recruitment\Domain\Contracts;

interface JobPositionRepositoryInterface
{
    public function findById(string $id): ?object;
    public function findByTenant(string $tenantId): iterable;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): void;
}
