<?php

namespace Modules\HR\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface EmployeeRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $tenantId, string $email): ?object;
    public function paginate(array $filters = [], int $perPage = 15): object;
    public function chunkActive(string $tenantId, int $chunkSize, callable $callback): void;
}
