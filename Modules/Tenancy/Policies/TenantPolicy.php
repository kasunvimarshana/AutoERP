<?php

declare(strict_types=1);

namespace Modules\Tenancy\Policies;

use App\Policies\BasePolicy;
use Modules\Tenancy\Entities\Tenant;

/**
 * Tenant Policy
 *
 * Authorization policy for tenant management.
 * Handles CRUD operations and custom abilities like activation, deactivation, and suspension.
 */
class TenantPolicy extends BasePolicy
{
    /**
     * Permission prefix for tenant operations.
     */
    protected string $permissionPrefix = 'tenant';

    /**
     * Determine whether the user can activate the tenant.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function activate($user, Tenant $tenant): bool
    {
        return $this->checkPermission($user, 'activate') &&
               $tenant->status !== 'active' &&
               ($this->hasAnyRole($user, ['super-admin', 'admin', 'tenant-manager']));
    }

    /**
     * Determine whether the user can deactivate the tenant.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function deactivate($user, Tenant $tenant): bool
    {
        return $this->checkPermission($user, 'deactivate') &&
               $tenant->status === 'active' &&
               ($this->hasAnyRole($user, ['super-admin', 'admin', 'tenant-manager']));
    }

    /**
     * Determine whether the user can suspend the tenant.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function suspend($user, Tenant $tenant): bool
    {
        return $this->checkPermission($user, 'suspend') &&
               $tenant->status !== 'suspended' &&
               ($this->hasAnyRole($user, ['super-admin', 'admin', 'tenant-manager']));
    }

    /**
     * Determine whether the user can view tenant statistics.
     *
     * @param  \Illuminate\Foundation\Auth\User  $user
     */
    public function viewStats($user, Tenant $tenant): bool
    {
        return $this->checkPermission($user, 'view-stats') &&
               ($this->hasAnyRole($user, ['super-admin', 'admin', 'tenant-manager', 'analyst']));
    }
}
