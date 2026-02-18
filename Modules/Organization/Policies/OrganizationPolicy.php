<?php

declare(strict_types=1);

namespace Modules\Organization\Policies;

use Modules\IAM\Entities\User;
use Modules\Organization\Entities\Organization;
use Modules\Organization\Services\OrganizationHierarchyService;

/**
 * Organization Policy
 *
 * Defines authorization rules for organization access with hierarchical support.
 *
 * Access Levels:
 * - 'own': User can only access their own organization
 * - 'children': User can access their organization and all child organizations
 * - 'tree': User can access entire tree (ancestors, self, descendants)
 * - 'tenant': User can access all organizations in their tenant
 *
 * Permissions are checked through the IAM module's permission system.
 */
class OrganizationPolicy
{
    public function __construct(
        private OrganizationHierarchyService $hierarchyService
    ) {}

    /**
     * Determine if user can view any organizations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('organization.view');
    }

    /**
     * Determine if user can view the organization.
     */
    public function view(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.view')) {
            return false;
        }

        // Check tenant isolation
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Get user's organization visibility setting
        $visibility = $user->getSetting('organization_visibility', 'own');

        return $this->hierarchyService->hasAccess($user->id, $organization->id, $visibility);
    }

    /**
     * Determine if user can create organizations.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('organization.create');
    }

    /**
     * Determine if user can update the organization.
     */
    public function update(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.update')) {
            return false;
        }

        // Check tenant isolation
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Users can update their own organization and child organizations
        $visibility = $user->getSetting('organization_edit_visibility', 'children');

        return $this->hierarchyService->hasAccess($user->id, $organization->id, $visibility);
    }

    /**
     * Determine if user can delete the organization.
     */
    public function delete(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.delete')) {
            return false;
        }

        // Check tenant isolation
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Cannot delete root organization
        if ($organization->isRoot()) {
            return false;
        }

        // Cannot delete organization with children
        if ($organization->children()->count() > 0) {
            return false;
        }

        // Users can only delete organizations in their subtree
        return $this->hierarchyService->isInSubtree($organization->id, $user->organization_id);
    }

    /**
     * Determine if user can restore the organization.
     */
    public function restore(User $user, Organization $organization): bool
    {
        return $this->delete($user, $organization);
    }

    /**
     * Determine if user can permanently delete the organization.
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        // Only super admins can force delete
        return $user->hasPermission('organization.force_delete') && $user->isSuperAdmin();
    }

    /**
     * Determine if user can move organization to a different parent.
     */
    public function move(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.move')) {
            return false;
        }

        // Check tenant isolation
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Cannot move root organization
        if ($organization->isRoot()) {
            return false;
        }

        // Can only move organizations in user's subtree
        return $this->hierarchyService->isInSubtree($organization->id, $user->organization_id);
    }

    /**
     * Determine if user can manage organization settings.
     */
    public function manageSettings(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.manage_settings')) {
            return false;
        }

        // Check tenant isolation
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Can manage settings for own organization or child organizations
        return $this->hierarchyService->isInSubtree($organization->id, $user->organization_id);
    }

    /**
     * Determine if user can manage organization features.
     */
    public function manageFeatures(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.manage_features')) {
            return false;
        }

        // Check tenant isolation
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Only organization admins or higher can manage features
        return $user->isSuperAdmin() || 
               $user->isOrganizationAdmin($organization->id) ||
               $this->hierarchyService->isInSubtree($organization->id, $user->organization_id);
    }

    /**
     * Determine if user can view organization hierarchy.
     */
    public function viewHierarchy(User $user, Organization $organization): bool
    {
        if (!$user->hasPermission('organization.view_hierarchy')) {
            return false;
        }

        // Check tenant isolation
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Can view hierarchy for accessible organizations
        $visibility = $user->getSetting('organization_visibility', 'own');
        
        return $this->hierarchyService->hasAccess($user->id, $organization->id, $visibility);
    }

    /**
     * Determine if user can switch to this organization.
     */
    public function switchTo(User $user, Organization $organization): bool
    {
        // Check tenant isolation
        if ($user->tenant_id !== $organization->tenant_id) {
            return false;
        }

        // Organization must be active
        if (!$organization->isActive()) {
            return false;
        }

        // Check if user has access based on visibility
        $visibility = $user->getSetting('organization_switch_visibility', 'tree');
        
        return $this->hierarchyService->hasAccess($user->id, $organization->id, $visibility);
    }
}
