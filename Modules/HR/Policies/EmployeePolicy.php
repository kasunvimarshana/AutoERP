<?php

declare(strict_types=1);

namespace Modules\HR\Policies;

use App\Policies\BasePolicy;
use Modules\HR\Entities\Employee;

/**
 * Employee Policy
 *
 * Authorization policy for employee management.
 * Handles CRUD operations and custom abilities like termination and reactivation.
 */
class EmployeePolicy extends BasePolicy
{
    /**
     * Permission prefix for employee operations.
     */
    protected string $permissionPrefix = 'employee';

    /**
     * Determine whether the user can terminate the employee.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function terminate($user, Employee $employee): bool
    {
        return $this->checkPermission($user, 'terminate') &&
               $this->checkTenantIsolation($user, $employee) &&
               $employee->status === 'active' &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'manager']));
    }

    /**
     * Determine whether the user can reactivate the employee.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reactivate($user, Employee $employee): bool
    {
        return $this->checkPermission($user, 'reactivate') &&
               $this->checkTenantIsolation($user, $employee) &&
               $employee->status === 'terminated' &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can view employee salary information.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewSalary($user, Employee $employee): bool
    {
        return $this->checkPermission($user, 'view-salary') &&
               $this->checkTenantIsolation($user, $employee) &&
               ($user->id === $employee->id || $this->hasAnyRole($user, ['admin', 'hr-manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can update employee salary.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function updateSalary($user, Employee $employee): bool
    {
        return $this->checkPermission($user, 'update-salary') &&
               $this->checkTenantIsolation($user, $employee) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can view employee performance reviews.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewPerformanceReviews($user, Employee $employee): bool
    {
        return $this->checkPermission($user, 'view-performance-reviews') &&
               $this->checkTenantIsolation($user, $employee) &&
               ($user->id === $employee->id || $this->hasAnyRole($user, ['admin', 'hr-manager', 'manager']));
    }

    /**
     * Determine whether the user can promote the employee.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function promote($user, Employee $employee): bool
    {
        return $this->checkPermission($user, 'promote') &&
               $this->checkTenantIsolation($user, $employee) &&
               $employee->status === 'active' &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can transfer the employee to another department.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function transfer($user, Employee $employee): bool
    {
        return $this->checkPermission($user, 'transfer') &&
               $this->checkTenantIsolation($user, $employee) &&
               $employee->status === 'active' &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'manager']));
    }

    /**
     * Determine whether the user can view employee documents.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewDocuments($user, Employee $employee): bool
    {
        return $this->checkPermission($user, 'view-documents') &&
               $this->checkTenantIsolation($user, $employee) &&
               ($user->id === $employee->id || $this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can manage employee documents.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function manageDocuments($user, Employee $employee): bool
    {
        return $this->checkPermission($user, 'manage-documents') &&
               $this->checkTenantIsolation($user, $employee) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can view the employee (override to add self-view).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function view($user, Employee $employee): bool
    {
        // Employees can always view their own profile
        if ($user->id === $employee->id) {
            return true;
        }

        return parent::view($user, $employee);
    }
}
