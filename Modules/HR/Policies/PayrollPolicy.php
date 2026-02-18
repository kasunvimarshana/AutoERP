<?php

declare(strict_types=1);

namespace Modules\HR\Policies;

use App\Policies\BasePolicy;
use Modules\HR\Entities\Payroll;

/**
 * Payroll Policy
 *
 * Authorization policy for payroll management.
 * Handles CRUD operations and custom abilities like processing and approving payroll.
 */
class PayrollPolicy extends BasePolicy
{
    /**
     * Permission prefix for payroll operations.
     */
    protected string $permissionPrefix = 'payroll';

    /**
     * Determine whether the user can process the payroll.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function process($user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'process') &&
               $this->checkTenantIsolation($user, $payroll) &&
               $payroll->status === 'draft' &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'payroll-manager']));
    }

    /**
     * Determine whether the user can approve the payroll.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function approve($user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'approve') &&
               $this->checkTenantIsolation($user, $payroll) &&
               $payroll->status === 'processed' &&
               ! $this->isOwner($user, $payroll) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can reject the payroll.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reject($user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'reject') &&
               $this->checkTenantIsolation($user, $payroll) &&
               $payroll->status === 'processed' &&
               ! $this->isOwner($user, $payroll) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can finalize the payroll.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function finalize($user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'finalize') &&
               $this->checkTenantIsolation($user, $payroll) &&
               $payroll->status === 'approved' &&
               ($this->hasAnyRole($user, ['admin', 'finance-manager']));
    }

    /**
     * Determine whether the user can view payroll details.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewDetails($user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'view-details') &&
               $this->checkTenantIsolation($user, $payroll) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'payroll-manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can generate payroll reports.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function generateReport($user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'generate-report') &&
               $this->checkTenantIsolation($user, $payroll) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'payroll-manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can export payroll data.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function export($user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'export') &&
               $this->checkTenantIsolation($user, $payroll) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'payroll-manager', 'finance-manager']));
    }

    /**
     * Determine whether the user can reverse the payroll.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function reverse($user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'reverse') &&
               $this->checkTenantIsolation($user, $payroll) &&
               $payroll->status === 'finalized' &&
               ($this->hasAnyRole($user, ['admin', 'super-admin']));
    }

    /**
     * Determine whether the user can recalculate the payroll.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function recalculate($user, Payroll $payroll): bool
    {
        return $this->checkPermission($user, 'recalculate') &&
               $this->checkTenantIsolation($user, $payroll) &&
               in_array($payroll->status, ['draft', 'rejected']) &&
               ($this->hasAnyRole($user, ['admin', 'hr-manager', 'payroll-manager']));
    }

    /**
     * Determine whether the user can delete the payroll (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function delete($user, Payroll $payroll): bool
    {
        return parent::delete($user, $payroll) &&
               in_array($payroll->status, ['draft', 'rejected']);
    }

    /**
     * Determine whether the user can update the payroll (override to add status check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, Payroll $payroll): bool
    {
        return parent::update($user, $payroll) &&
               in_array($payroll->status, ['draft', 'rejected']);
    }
}
