<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\CRM\Models\Lead;

/**
 * Lead authorization policy
 * 
 * Enforces fine-grained RBAC/ABAC with strict tenant isolation.
 */
class LeadPolicy
{
    /**
     * Determine whether the user can view any leads.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-leads');
    }

    /**
     * Determine whether the user can view the lead.
     */
    public function view(User $user, Lead $lead): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $lead->tenant_id) {
            return false;
        }

        if (!$user->can('view-leads')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Organization-level check
        if ($user->organization_id && $lead->organization_id) {
            if ($user->organization_id !== $lead->organization_id) {
                return false;
            }
        }

        // Branch-level check
        if ($user->branch_id && $lead->branch_id) {
            return $user->branch_id === $lead->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can create leads.
     */
    public function create(User $user): bool
    {
        return $user->can('create-leads');
    }

    /**
     * Determine whether the user can update the lead.
     */
    public function update(User $user, Lead $lead): bool
    {
        if ($user->tenant_id !== $lead->tenant_id) {
            return false;
        }

        if (!$user->can('edit-leads')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id && $lead->organization_id) {
            if ($user->organization_id !== $lead->organization_id) {
                return false;
            }
        }

        if ($user->branch_id && $lead->branch_id) {
            return $user->branch_id === $lead->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the lead.
     */
    public function delete(User $user, Lead $lead): bool
    {
        if ($user->tenant_id !== $lead->tenant_id) {
            return false;
        }

        if (!$user->can('delete-leads')) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id && $lead->organization_id) {
            if ($user->organization_id !== $lead->organization_id) {
                return false;
            }
        }

        if ($user->branch_id && $lead->branch_id) {
            return $user->branch_id === $lead->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the lead.
     */
    public function restore(User $user, Lead $lead): bool
    {
        return $this->update($user, $lead);
    }

    /**
     * Determine whether the user can permanently delete the lead.
     */
    public function forceDelete(User $user, Lead $lead): bool
    {
        if ($user->tenant_id !== $lead->tenant_id) {
            return false;
        }

        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        return $user->can('delete-leads');
    }
}
