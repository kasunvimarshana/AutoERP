<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Tenant\Models\Branch;

/**
 * Branch authorization policy
 * 
 * Enforces fine-grained RBAC/ABAC with strict tenant isolation,
 * organization-level and branch-level restrictions.
 */
class BranchPolicy
{
    /**
     * Determine whether the user can view any branches.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can view branches
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-branches');
    }

    /**
     * Determine whether the user can view the branch.
     * 
     * Enforces tenant isolation and organization/branch restrictions.
     * 
     * @param User $user The authenticated user
     * @param Branch $branch The branch to view
     * @return bool True if the user can view this branch
     */
    public function view(User $user, Branch $branch): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $branch->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('view-branches')) {
            return false;
        }

        // Super admins can view all branches in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Organization-level restriction: users can view branches in their organization
        if ($user->organization_id && $branch->organization_id) {
            if ($user->organization_id !== $branch->organization_id) {
                return false;
            }
        }

        // Branch-level restriction: users can view their own branch
        if ($user->branch_id && $branch->id) {
            return $user->branch_id === $branch->id;
        }

        return true;
    }

    /**
     * Determine whether the user can create branches.
     * 
     * Only admins and super admins can create branches.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can create branches
     */
    public function create(User $user): bool
    {
        // Must have create permission
        if (!$user->can('create-branches')) {
            return false;
        }

        // Super admins and admins can create branches
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Determine whether the user can update the branch.
     * 
     * Enforces tenant isolation and organization restrictions.
     * 
     * @param User $user The authenticated user
     * @param Branch $branch The branch to update
     * @return bool True if the user can update this branch
     */
    public function update(User $user, Branch $branch): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $branch->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('edit-branches')) {
            return false;
        }

        // Super admins can edit all branches in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can edit branches in their organization
        if ($user->hasRole('admin')) {
            if ($user->organization_id && $branch->organization_id) {
                return $user->organization_id === $branch->organization_id;
            }
            return true;
        }

        // Branch managers can edit their own branch
        if ($user->hasRole('branch-manager')) {
            return $user->branch_id === $branch->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the branch.
     * 
     * Enforces tenant isolation and organization restrictions.
     * 
     * @param User $user The authenticated user
     * @param Branch $branch The branch to delete
     * @return bool True if the user can delete this branch
     */
    public function delete(User $user, Branch $branch): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $branch->tenant_id) {
            return false;
        }

        // Cannot delete your own branch
        if ($user->branch_id === $branch->id) {
            return false;
        }

        // Check base permission
        if (!$user->can('delete-branches')) {
            return false;
        }

        // Super admins can delete any branch in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can delete branches in their organization
        if ($user->hasRole('admin')) {
            if ($user->organization_id && $branch->organization_id) {
                return $user->organization_id === $branch->organization_id;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the branch.
     * 
     * @param User $user The authenticated user
     * @param Branch $branch The branch to restore
     * @return bool True if the user can restore this branch
     */
    public function restore(User $user, Branch $branch): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $branch->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('edit-branches')) {
            return false;
        }

        // Super admins can restore any branch
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can restore branches in their organization
        if ($user->hasRole('admin')) {
            if ($user->organization_id && $branch->organization_id) {
                return $user->organization_id === $branch->organization_id;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the branch.
     * 
     * Only super admins and admins can force delete branches.
     * 
     * @param User $user The authenticated user
     * @param Branch $branch The branch to permanently delete
     * @return bool True if the user can permanently delete this branch
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $branch->tenant_id) {
            return false;
        }

        // Cannot delete your own branch
        if ($user->branch_id === $branch->id) {
            return false;
        }

        // Only super admins and admins can force delete
        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        return $user->can('delete-branches');
    }

    /**
     * Determine whether the user can manage branch settings.
     * 
     * @param User $user The authenticated user
     * @param Branch $branch The branch to manage settings for
     * @return bool True if the user can manage settings
     */
    public function manageSettings(User $user, Branch $branch): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $branch->tenant_id) {
            return false;
        }

        // Super admins can manage any branch settings
        if ($user->hasRole('super-admin')) {
            return $user->can('manage-branch-settings');
        }

        // Admins can manage settings for branches in their organization
        if ($user->hasRole('admin')) {
            if ($user->organization_id && $branch->organization_id) {
                if ($user->organization_id !== $branch->organization_id) {
                    return false;
                }
            }
            return $user->can('manage-branch-settings');
        }

        // Branch managers can manage their own branch settings
        if ($user->hasRole('branch-manager')) {
            return $user->branch_id === $branch->id && $user->can('manage-branch-settings');
        }

        return false;
    }

    /**
     * Determine whether the user can assign users to the branch.
     * 
     * @param User $user The authenticated user
     * @param Branch $branch The branch to assign users to
     * @return bool True if the user can assign users
     */
    public function assignUsers(User $user, Branch $branch): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $branch->tenant_id) {
            return false;
        }

        // Super admins can assign users to any branch
        if ($user->hasRole('super-admin')) {
            return $user->can('assign-branch-users');
        }

        // Admins can assign users to branches in their organization
        if ($user->hasRole('admin')) {
            if ($user->organization_id && $branch->organization_id) {
                if ($user->organization_id !== $branch->organization_id) {
                    return false;
                }
            }
            return $user->can('assign-branch-users');
        }

        // Branch managers can assign users to their branch
        if ($user->hasRole('branch-manager')) {
            return $user->branch_id === $branch->id && $user->can('assign-branch-users');
        }

        return false;
    }
}
