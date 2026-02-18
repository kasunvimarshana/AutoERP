<?php

declare(strict_types=1);

namespace Modules\HR\Policies;

use App\Policies\BasePolicy;
use Modules\HR\Entities\Attendance;

/**
 * Attendance Policy
 *
 * Authorization policy for attendance management.
 * Handles CRUD operations and custom abilities like check-in and check-out.
 */
class AttendancePolicy extends BasePolicy
{
    /**
     * Permission prefix for attendance operations.
     */
    protected string $permissionPrefix = 'attendance';

    /**
     * Determine whether the user can check in.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function checkIn($user): bool
    {
        return $this->checkPermission($user, 'check-in');
    }

    /**
     * Determine whether the user can check out.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function checkOut($user, Attendance $attendance): bool
    {
        return $this->checkPermission($user, 'check-out') &&
               $this->checkTenantIsolation($user, $attendance) &&
               ($this->isOwner($user, $attendance) || $this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can approve the attendance.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approve($user, Attendance $attendance): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $attendance) &&
               ! $this->isOwner($user, $attendance) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'manager']) ||
                $this->isSameDepartment($user, $attendance));
    }

    /**
     * Determine whether the user can reject the attendance.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reject($user, Attendance $attendance): bool
    {
        return $this->checkPermission($user, 'reject') &&
               $this->checkTenantIsolation($user, $attendance) &&
               ! $this->isOwner($user, $attendance) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'manager']) ||
                $this->isSameDepartment($user, $attendance));
    }

    /**
     * Determine whether the user can correct the attendance.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function correct($user, Attendance $attendance): bool
    {
        return $this->checkPermission($user, 'correct') &&
               $this->checkTenantIsolation($user, $attendance) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can view attendance reports.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewReports($user): bool
    {
        return $this->checkPermission($user, 'view-reports') &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'manager']));
    }

    /**
     * Determine whether the user can export attendance data.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function export($user): bool
    {
        return $this->checkPermission($user, 'export') &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can override attendance.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function override($user, Attendance $attendance): bool
    {
        return $this->checkPermission($user, 'override') &&
               $this->checkTenantIsolation($user, $attendance) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can view the attendance (override to add owner check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function view($user, Attendance $attendance): bool
    {
        // Users can always view their own attendance
        if ($this->isOwner($user, $attendance)) {
            return true;
        }

        return parent::view($user, $attendance);
    }

    /**
     * Determine whether the user can update the attendance (override to add owner check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, Attendance $attendance): bool
    {
        return parent::update($user, $attendance) &&
               ($this->isOwner($user, $attendance) || $this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can delete the attendance (override to add role check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, Attendance $attendance): bool
    {
        return parent::delete($user, $attendance) &&
               $this->hasAnyRole($user, ['admin', 'hr-manager']);
    }
}
