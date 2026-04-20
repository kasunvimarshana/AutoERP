<?php

declare(strict_types=1);

namespace Modules\Employee\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Employee\Domain\Entities\Employee;

interface EmployeeRepositoryInterface extends RepositoryInterface
{
    public function save(Employee $employee): Employee;

    public function findByTenantAndUserId(int $tenantId, int $userId): ?Employee;

    public function findByTenantAndEmployeeCode(int $tenantId, string $employeeCode): ?Employee;

    public function find(int|string $id, array $columns = ['*']): ?Employee;
}
