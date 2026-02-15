<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Modules\Tenant\Models\Organization;

/**
 * Organization authorization policy
 * 
 * Enforces fine-grained RBAC/ABAC with strict tenant isolation
 * and hierarchical organization restrictions.
 */
class OrganizationPolicy
{
    /**
     * Determine whether the user can view any organizations.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can view organizations
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-organizations');
    }

    /**
     * Determine whether the user can view the organization.
     * 
     * Enforces tenant isolation. Users can only view organizations within their tenant.
     * Organization-level users can only view their own organization and its children.
     * 
     * @param User $user The authenticated user
     * @param Organization $organization The organization to view
     * @return bool True if the user can view this organization
     */
    public function view(User $user, Organization $organization): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('view-organizations')) {
            return false;
        }

        // Super admins can view all organizations in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Users with organization_id can view their organization and child organizations
        if ($user->organization_id) {
            // Can view own organization
            if ($user->organization_id === $organization->id) {
                return true;
            }

            // Can view child organizations (check if organization is a descendant)
            return $this->isDescendantOf($organization, $user->organization_id);
        }

        return true;
    }

    /**
     * Determine whether the user can create organizations.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can create organizations
     */
    public function create(User $user): bool
    {
        // Must have create permission
        if (!$user->can('create-organizations')) {
            return false;
        }

        // Super admins and admins can create organizations
        return $user->hasAnyRole(['super-admin', 'admin']);
    }

    /**
     * Determine whether the user can update the organization.
     * 
     * Enforces tenant isolation and hierarchical restrictions.
     * 
     * @param User $user The authenticated user
     * @param Organization $organization The organization to update
     * @return bool True if the user can update this organization
     */
    public function update(User $user, Organization $organization): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('edit-organizations')) {
            return false;
        }

        // Super admins can edit all organizations in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can edit their organization and child organizations
        if ($user->hasRole('admin')) {
            if ($user->organization_id) {
                // Can edit own organization
                if ($user->organization_id === $organization->id) {
                    return true;
                }

                // Can edit child organizations
                return $this->isDescendantOf($organization, $user->organization_id);
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the organization.
     * 
     * Enforces tenant isolation and hierarchical restrictions.
     * 
     * @param User $user The authenticated user
     * @param Organization $organization The organization to delete
     * @return bool True if the user can delete this organization
     */
    public function delete(User $user, Organization $organization): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Cannot delete your own organization
        if ($user->organization_id === $organization->id) {
            return false;
        }

        // Check base permission
        if (!$user->can('delete-organizations')) {
            return false;
        }

        // Super admins can delete any organization in their tenant
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can delete child organizations
        if ($user->hasRole('admin')) {
            if ($user->organization_id) {
                return $this->isDescendantOf($organization, $user->organization_id);
            }
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the organization.
     * 
     * @param User $user The authenticated user
     * @param Organization $organization The organization to restore
     * @return bool True if the user can restore this organization
     */
    public function restore(User $user, Organization $organization): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Check base permission
        if (!$user->can('edit-organizations')) {
            return false;
        }

        // Super admins can restore any organization
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Admins can restore organizations
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the organization.
     * 
     * Only super admins and admins can force delete organizations.
     * 
     * @param User $user The authenticated user
     * @param Organization $organization The organization to permanently delete
     * @return bool True if the user can permanently delete this organization
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        // Tenant isolation is mandatory
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Cannot delete your own organization
        if ($user->organization_id === $organization->id) {
            return false;
        }

        // Only super admins and admins can force delete
        if (!$user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        return $user->can('delete-organizations');
    }

    /**
     * Check if an organization is a descendant of another organization.
     * 
     * Uses recursive CTE query for efficient hierarchical lookup.
     * 
     * @param Organization $organization The organization to check
     * @param int $ancestorId The potential ancestor organization ID
     * @return bool True if organization is a descendant
     */
    protected function isDescendantOf(Organization $organization, int $ancestorId): bool
    {
        // Direct parent check
        if ($organization->parent_id === $ancestorId) {
            return true;
        }
        
        // Use recursive query to check ancestry
        $isDescendant = \DB::table('organizations')
            ->whereRaw('id = ?', [$organization->id])
            ->whereRaw('EXISTS (
                WITH RECURSIVE org_tree AS (
                    SELECT id, parent_id FROM organizations WHERE id = ?
                    UNION ALL
                    SELECT o.id, o.parent_id 
                    FROM organizations o
                    INNER JOIN org_tree ot ON o.id = ot.parent_id
                )
                SELECT 1 FROM org_tree WHERE id = ?
            )', [$organization->id, $ancestorId])
            ->exists();
        
        return $isDescendant;
    }
}
