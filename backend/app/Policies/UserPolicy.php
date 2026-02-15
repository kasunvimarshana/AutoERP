<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * User authorization policy
 * 
 * Enforces fine-grained RBAC/ABAC with strict tenant isolation,
 * organization-level and branch-level restrictions.
 */
class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can view users
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-users');
    }

    /**
     * Determine whether the user can view the model.
     * 
     * Enforces tenant isolation and organization/branch restrictions.
     * 
     * @param User $user The authenticated user
     * @param User $model The user to view
     * @return bool True if the user can view this user
     */
    public function view(User $user, User $model): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('view-users')) {
            return false;
        }

        // Super admins can view all users in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Organization-level restriction
        if ($user->organization_id && $model->organization_id) {
            if ($user->organization_id !== $model->organization_id) {
                return false;
            }
        }

        // Branch-level restriction
        if ($user->branch_id && $model->branch_id) {
            return $user->branch_id === $model->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can create users.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can create users
     */
    public function create(User $user): bool
    {
        return $user->can('create-users');
    }

    /**
     * Determine whether the user can update the model.
     * 
     * Enforces tenant isolation and prevents privilege escalation.
     * 
     * @param User $user The authenticated user
     * @param User $model The user to update
     * @return bool True if the user can update this user
     */
    public function update(User $user, User $model): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('edit-users')) {
            return false;
        }

        // Super admins can edit anyone in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can edit users in their organization
        if ($user->hasRole('admin')) {
            // Organization-level restriction
            if ($user->organization_id && $model->organization_id) {
                return $user->organization_id === $model->organization_id;
            }
            return true;
        }

        // Branch managers can edit users in their branch
        if ($user->hasRole('branch-manager')) {
            if ($user->branch_id && $model->branch_id) {
                return $user->branch_id === $model->branch_id;
            }
        }

        // Users can edit themselves
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     * 
     * Enforces tenant isolation and prevents self-deletion.
     * 
     * @param User $user The authenticated user
     * @param User $model The user to delete
     * @return bool True if the user can delete this user
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Tenant isolation is mandatory
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('delete-users')) {
            return false;
        }

        // Super admins can delete anyone in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can delete users in their organization
        if ($user->hasRole('admin')) {
            // Cannot delete other admins or super admins
            if ($model->hasAnyRole(['admin', 'super-admin'])) {
                return false;
            }

            // Organization-level restriction
            if ($user->organization_id && $model->organization_id) {
                return $user->organization_id === $model->organization_id;
            }
            return true;
        }

        // Branch managers can delete users in their branch
        if ($user->hasRole('branch-manager')) {
            // Cannot delete admins or super admins
            if ($model->hasAnyRole(['admin', 'super-admin', 'branch-manager'])) {
                return false;
            }

            if ($user->branch_id && $model->branch_id) {
                return $user->branch_id === $model->branch_id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     * 
     * @param User $user The authenticated user
     * @param User $model The user to restore
     * @return bool True if the user can restore this user
     */
    public function restore(User $user, User $model): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('edit-users')) {
            return false;
        }

        // Super admins can restore anyone
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can restore users in their organization
        if ($user->hasRole('admin')) {
            if ($user->organization_id && $model->organization_id) {
                return $user->organization_id === $model->organization_id;
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * 
     * Only super admins and admins can force delete.
     * 
     * @param User $user The authenticated user
     * @param User $model The user to permanently delete
     * @return bool True if the user can permanently delete this user
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Tenant isolation is mandatory
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        // Only super admins and admins can force delete
        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        // Check base permission
        return $user->can('delete-users');
    }

    /**
     * Determine whether the user can assign roles to the model.
     * 
     * @param User $user The authenticated user
     * @param User $model The user to assign roles to
     * @return bool True if the user can assign roles
     */
    public function assignRoles(User $user, User $model): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        // Only super admins and admins can assign roles
        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        return $user->can('assign-roles');
    }

    /**
     * Determine whether the user can assign permissions to the model.
     * 
     * @param User $user The authenticated user
     * @param User $model The user to assign permissions to
     * @return bool True if the user can assign permissions
     */
    public function assignPermissions(User $user, User $model): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        // Only super admins and admins can assign permissions
        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        return $user->can('assign-permissions');
    }
}

