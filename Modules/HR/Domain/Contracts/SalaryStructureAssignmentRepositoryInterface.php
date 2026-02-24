<?php

namespace Modules\HR\Domain\Contracts;

use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface SalaryStructureAssignmentRepositoryInterface extends RepositoryInterface
{
    public function findActiveByEmployee(string $tenantId, string $employeeId): ?object;

    public function paginate(array $filters = [], int $perPage = 15): object;
}
