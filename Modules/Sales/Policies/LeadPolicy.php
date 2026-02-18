<?php

declare(strict_types=1);

namespace Modules\Sales\Policies;

use App\Policies\BasePolicy;
use Modules\Sales\Entities\Lead;

/**
 * Lead Policy
 *
 * Authorization policy for lead management.
 * Handles CRUD operations and custom abilities like converting leads to customers.
 */
class LeadPolicy extends BasePolicy
{
    /**
     * Permission prefix for lead operations.
     */
    protected string $permissionPrefix = 'lead';

    /**
     * Determine whether the user can convert the lead to a customer.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function convertToCustomer($user, Lead $lead): bool
    {
        return $this->checkPermission($user, 'convert-to-customer') &&
               $this->checkTenantIsolation($user, $lead) &&
               $lead->status === 'qualified';
    }

    /**
     * Determine whether the user can assign the lead to another user.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function assign($user, Lead $lead): bool
    {
        return $this->checkPermission($user, 'assign') &&
               $this->checkTenantIsolation($user, $lead) &&
               ($this->hasAnyRole($user, ['admin', 'manager', 'sales-manager']));
    }

    /**
     * Determine whether the user can qualify the lead.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function qualify($user, Lead $lead): bool
    {
        return $this->checkPermission($user, 'qualify') &&
               $this->checkTenantIsolation($user, $lead) &&
               ($this->isOwner($user, $lead) || $this->hasAnyRole($user, ['admin', 'manager', 'sales-manager']));
    }

    /**
     * Determine whether the user can disqualify the lead.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function disqualify($user, Lead $lead): bool
    {
        return $this->checkPermission($user, 'disqualify') &&
               $this->checkTenantIsolation($user, $lead) &&
               ($this->isOwner($user, $lead) || $this->hasAnyRole($user, ['admin', 'manager', 'sales-manager']));
    }

    /**
     * Determine whether the user can view lead activities.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewActivities($user, Lead $lead): bool
    {
        return $this->checkPermission($user, 'view-activities') &&
               $this->checkTenantIsolation($user, $lead);
    }

    /**
     * Determine whether the user can update the model (override to add owner check).
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function update($user, Lead $lead): bool
    {
        return parent::update($user, $lead) &&
               ($this->isOwner($user, $lead) || $this->hasAnyRole($user, ['admin', 'manager', 'sales-manager']));
    }
}
