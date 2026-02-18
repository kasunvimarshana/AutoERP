<?php

declare(strict_types=1);

namespace Modules\HR\Policies;

use App\Policies\BasePolicy;
use Modules\HR\Entities\Leave;

/**
 * Leave Policy
 *
 * Authorization policy for leave request management.
 * Handles CRUD operations and custom abilities like approving and rejecting leave requests.
 */
class LeavePolicy extends BasePolicy
{
    /**
     * Permission prefix for leave operations.
     */
    protected string $permissionPrefix = 'leave';

    /**
     * Determine whether the user can approve the leave request.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approve($user, Leave $leave): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $leave) &&
               $leave->status === 'pending' &&
               ! $this->isOwner($user, $leave) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'manager']) ||
                $this->isSameDepartment($user, $leave));
    }

    /**
     * Determine whether the user can reject the leave request.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reject($user, Leave $leave): bool
    {
        return $this->checkPermission($user, 'reject') &&
               $this->checkTenantIsolation($user, $leave) &&
               $leave->status === 'pending' &&
               ! $this->isOwner($user, $leave) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'manager']) ||
                $this->isSameDepartment($user, $leave));
    }

    /**
     * Determine whether the user can cancel the leave request.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function cancel($user, Leave $leave): bool
    {
        return $this->checkPermission($user, 'cancel') &&
               $this->checkTenantIsolation($user, $leave) &&
               in_array($leave->status, ['pending', 'approved']) &&
               ($this->isOwner($user, $leave) || $this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can view leave balance.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewBalance($user, Leave $leave): bool
    {
        return $this->checkPermission($user, 'view-balance') &&
               $this->checkTenantIsolation($user, $leave) &&
               ($this->isOwner($user, $leave) || $this->hasAnyRole($user, ['admin', 'hr-manager', 'manager']));
    }

    /**
     * Determine whether the user can override leave balance.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function overrideBalance($user, Leave $leave): bool
    {
        return $this->checkPermission($user, 'override-balance') &&
               $this->checkTenantIsolation($user, $leave) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager']));
    }

    /**
     * Determine whether the user can submit the leave request.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function submit($user): bool
    {
        return $this->checkPermission($user, 'submit');
    }

    /**
     * Determine whether the user can view the leave request (override to add owner check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function view($user, Leave $leave): bool
    {
        // Users can always view their own leave requests
        if ($this->isOwner($user, $leave)) {
            return true;
        }

        return parent::view($user, $leave);
    }

    /**
     * Determine whether the user can update the leave request (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, Leave $leave): bool
    {
        return parent::update($user, $leave) &&
               $leave->status === 'draft' &&
               $this->isOwner($user, $leave);
    }

    /**
     * Determine whether the user can delete the leave request (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, Leave $leave): bool
    {
        return parent::delete($user, $leave) &&
               in_array($leave->status, ['draft', 'rejected']) &&
               ($this->isOwner($user, $leave) || $this->hasAnyRole($user, ['admin', 'hr-manager']));
    }
}
