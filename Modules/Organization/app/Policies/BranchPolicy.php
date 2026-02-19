<?php

declare(strict_types=1);

namespace Modules\Organization\Policies;

use App\Core\Policies\BasePolicy;
use App\Models\User;
use Modules\Organization\Models\Branch;

/**
 * Branch Authorization Policy
 *
 * Tenant-aware authorization for branch management operations
 * Implements both RBAC and ABAC patterns
 */
class BranchPolicy extends BasePolicy
{
    /**
     * Determine if user can view any branches
     */
    public function viewAny(User $user): bool
    {
        return $user->can('branch.list') || $user->can('branch.read');
    }

    /**
     * Determine if user can view specific branch
     */
    public function view(User $user, Branch $branch): bool
    {
        if ($user->can('branch.read')) {
            return $this->isSameTenant($user, $branch);
        }

        return false;
    }

    /**
     * Determine if user can create branches
     */
    public function create(User $user): bool
    {
        return $user->can('branch.create');
    }

    /**
     * Determine if user can update specific branch
     */
    public function update(User $user, Branch $branch): bool
    {
        if ($user->can('branch.update')) {
            return $this->isSameTenant($user, $branch);
        }

        return false;
    }

    /**
     * Determine if user can delete specific branch
     */
    public function delete(User $user, Branch $branch): bool
    {
        if ($user->can('branch.delete')) {
            return $this->isSameTenant($user, $branch);
        }

        return false;
    }

    /**
     * Determine if user can restore deleted branch
     */
    public function restore(User $user, Branch $branch): bool
    {
        if ($user->can('branch.delete')) {
            return $this->isSameTenant($user, $branch);
        }

        return false;
    }

    /**
     * Determine if user can permanently delete branch
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        // Only super admins can permanently delete
        return $this->isSuperAdmin($user) && $this->isSameTenant($user, $branch);
    }
}
