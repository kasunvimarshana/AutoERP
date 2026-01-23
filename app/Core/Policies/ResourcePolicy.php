<?php

declare(strict_types=1);

namespace App\Core\Policies;

/**
 * Resource Policy Example
 * 
 * Example policy demonstrating ABAC (Attribute-Based Access Control)
 * Combines roles, permissions, ownership, and tenant isolation
 */
class ResourcePolicy extends BasePolicy
{
    /**
     * Determine if user can view any resources
     *
     * @param mixed $user
     * @return bool
     */
    public function viewAny(mixed $user): bool
    {
        // Super admin can always view
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check specific permission
        return $user->can('resource.read');
    }

    /**
     * Determine if user can view the resource
     *
     * @param mixed $user
     * @param mixed $model
     * @return bool
     */
    public function view(mixed $user, mixed $model): bool
    {
        // Super admin can always view
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Must be in same tenant
        if (!$this->isSameTenant($user, $model)) {
            return false;
        }

        // Owner can view their own resources
        if ($this->isOwner($user, $model)) {
            return true;
        }

        // Check permission
        return $user->can('resource.read');
    }

    /**
     * Determine if user can create resources
     *
     * @param mixed $user
     * @return bool
     */
    public function create(mixed $user): bool
    {
        // Super admin can always create
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check permission
        return $user->can('resource.create');
    }

    /**
     * Determine if user can update the resource
     *
     * @param mixed $user
     * @param mixed $model
     * @return bool
     */
    public function update(mixed $user, mixed $model): bool
    {
        // Super admin can always update
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Must be in same tenant
        if (!$this->isSameTenant($user, $model)) {
            return false;
        }

        // Owner can update their own resources
        if ($this->isOwner($user, $model)) {
            return true;
        }

        // Check permission
        return $user->can('resource.update');
    }

    /**
     * Determine if user can delete the resource
     *
     * @param mixed $user
     * @param mixed $model
     * @return bool
     */
    public function delete(mixed $user, mixed $model): bool
    {
        // Super admin can always delete
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Must be in same tenant
        if (!$this->isSameTenant($user, $model)) {
            return false;
        }

        // Owner cannot delete if resource is locked
        if (isset($model->is_locked) && $model->is_locked) {
            return false;
        }

        // Check permission
        return $user->can('resource.delete');
    }

    /**
     * Determine if user can restore the resource
     *
     * @param mixed $user
     * @param mixed $model
     * @return bool
     */
    public function restore(mixed $user, mixed $model): bool
    {
        // Only admins can restore
        return $this->isAdmin($user) && $this->isSameTenant($user, $model);
    }

    /**
     * Determine if user can permanently delete the resource
     *
     * @param mixed $user
     * @param mixed $model
     * @return bool
     */
    public function forceDelete(mixed $user, mixed $model): bool
    {
        // Only super admin can force delete
        return $this->isSuperAdmin($user);
    }
}
