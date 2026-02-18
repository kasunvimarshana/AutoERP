<?php

declare(strict_types=1);

namespace Modules\HR\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\HR\Entities\Payroll;

/**
 * Payroll Repository Interface
 *
 * Defines the contract for payroll data access operations.
 */
interface PayrollRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find payroll by payroll number.
     */
    public function findByPayrollNumber(string $payrollNumber): ?Payroll;

    /**
     * Get payroll by employee.
     */
    public function getByEmployee(int $employeeId): Collection;

    /**
     * Get payroll by month and year.
     */
    public function getByMonthYear(int $month, int $year): Collection;

    /**
     * Get payroll by employee, month, and year.
     */
    public function getByEmployeeMonthYear(int $employeeId, int $month, int $year): ?Payroll;

    /**
     * Get payroll by status.
     */
    public function getByStatus(string $status): Collection;
}
