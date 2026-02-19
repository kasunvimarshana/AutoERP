<?php

declare(strict_types=1);

namespace Modules\Organization\Policies;

use App\Core\Policies\BasePolicy;
use App\Models\User;
use Modules\Organization\Models\Organization;

/**
 * Organization Authorization Policy
 *
 * Tenant-aware authorization for organization management operations
 * Implements both RBAC and ABAC patterns
 */
class OrganizationPolicy extends BasePolicy
{
    /**
     * Determine if user can view any organizations
     */
    public function viewAny(User $user): bool
    {
        return $user->can('organization.list') || $user->can('organization.read');
    }

    /**
     * Determine if user can view specific organization
     */
    public function view(User $user, Organization $organization): bool
    {
        if ($user->can('organization.read')) {
            return $this->isSameTenant($user, $organization);
        }

        return false;
    }

    /**
     * Determine if user can create organizations
     */
    public function create(User $user): bool
    {
        return $user->can('organization.create');
    }

    /**
     * Determine if user can update specific organization
     */
    public function update(User $user, Organization $organization): bool
    {
        if ($user->can('organization.update')) {
            return $this->isSameTenant($user, $organization);
        }

        return false;
    }

    /**
     * Determine if user can delete specific organization
     */
    public function delete(User $user, Organization $organization): bool
    {
        if ($user->can('organization.delete')) {
            return $this->isSameTenant($user, $organization);
        }

        return false;
    }

    /**
     * Determine if user can restore deleted organization
     */
    public function restore(User $user, Organization $organization): bool
    {
        if ($user->can('organization.delete')) {
            return $this->isSameTenant($user, $organization);
        }

        return false;
    }

    /**
     * Determine if user can permanently delete organization
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        // Only super admins can permanently delete
        return $this->isSuperAdmin($user) && $this->isSameTenant($user, $organization);
    }
}
