<?php

namespace Modules\HR\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface DepartmentRepositoryInterface extends RepositoryInterface
{
    public function findByName(string $tenantId, string $name): ?object;
    public function paginate(array $filters = [], int $perPage = 15): object;
}
