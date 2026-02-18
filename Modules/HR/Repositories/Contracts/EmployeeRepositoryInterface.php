<?php

declare(strict_types=1);

namespace Modules\HR\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\HR\Entities\Employee;

/**
 * Employee Repository Interface
 *
 * Defines the contract for employee data access operations.
 */
interface EmployeeRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find employee by email.
     */
    public function findByEmail(string $email): ?Employee;

    /**
     * Find employee by employee number.
     */
    public function findByEmployeeNumber(string $employeeNumber): ?Employee;

    /**
     * Get employees by department.
     */
    public function getByDepartment(int $departmentId): Collection;

    /**
     * Get employees by position.
     */
    public function getByPosition(int $positionId): Collection;

    /**
     * Get active employees.
     */
    public function getActiveEmployees(): Collection;

    /**
     * Search employees by name or employee number.
     */
    public function search(string $query): Collection;

    /**
     * Get employees reporting to a manager.
     */
    public function getSubordinates(int $managerId): Collection;
}
