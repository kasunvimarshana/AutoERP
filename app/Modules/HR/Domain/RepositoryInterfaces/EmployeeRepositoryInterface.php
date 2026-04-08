<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\Employee;

interface EmployeeRepositoryInterface extends RepositoryInterface
{
    public function save(Employee $employee): Employee;

    /**
     * Find an employee by their linked user ID.
     */
    public function findByUserId(int $userId): ?Employee;

    /**
     * Return all employees belonging to a given department.
     *
     * @return array<int, Employee>
     */
    public function getByDepartment(int $departmentId): array;

    /**
     * Return all employees managed by a given manager.
     *
     * @return array<int, Employee>
     */
    public function getByManager(int $managerId): array;
}
