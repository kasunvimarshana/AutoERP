<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * ABAC policy that enforces tenant isolation for every resource operation.
 *
 * A "resource" is any Eloquent model (or plain array) that carries a tenant_id
 * attribute.  Admins may manage anything within their own tenant; managers may
 * read and write; regular users may only read their own tenant's resources.
 */
class TenantPolicy
{
    use HandlesAuthorization;

    // -------------------------------------------------------------------------
    // Before hook — super-admin bypass
    // -------------------------------------------------------------------------

    /**
     * Grant all abilities to super-admins before any specific check runs.
     * Return null to fall through to the individual methods for everyone else.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Generic ABAC checks
    // -------------------------------------------------------------------------

    /**
     * The user may view the resource if it belongs to their tenant.
     *
     * @param  object|array<string, mixed>  $resource
     */
    public function view(User $user, object|array $resource): bool
    {
        return $this->sameTenant($user, $resource);
    }

    /**
     * The user may create a resource inside their tenant.
     * Optionally validate a target tenant_id sent in the payload.
     *
     * @param  array<string, mixed>|null  $data
     */
    public function create(User $user, ?array $data = null): bool
    {
        if ($data !== null && isset($data['tenant_id'])) {
            return (int) $user->tenant_id === (int) $data['tenant_id'];
        }

        return true;
    }

    /**
     * The user may update the resource if it belongs to their tenant and they
     * have at least the "manager" or "admin" role.
     *
     * @param  object|array<string, mixed>  $resource
     */
    public function update(User $user, object|array $resource): bool
    {
        return $this->sameTenant($user, $resource)
            && $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Only admins may delete resources, and only within their own tenant.
     *
     * @param  object|array<string, mixed>  $resource
     */
    public function delete(User $user, object|array $resource): bool
    {
        return $this->sameTenant($user, $resource)
            && $user->hasRole('admin');
    }

    /**
     * The user may list/index resources only within their own tenant.
     */
    public function viewAny(User $user, ?Tenant $tenant = null): bool
    {
        if ($tenant === null) {
            return true;
        }

        return (int) $user->tenant_id === (int) $tenant->id;
    }

    // -------------------------------------------------------------------------
    // Tenant self-management
    // -------------------------------------------------------------------------

    /**
     * Only admins may update their own tenant's settings.
     */
    public function manageTenant(User $user, Tenant $tenant): bool
    {
        return (int) $user->tenant_id === (int) $tenant->id
            && $user->hasRole('admin');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Check that the resource's tenant_id matches the acting user's tenant.
     *
     * @param  object|array<string, mixed>  $resource
     */
    private function sameTenant(User $user, object|array $resource): bool
    {
        $resourceTenantId = is_array($resource)
            ? ($resource['tenant_id'] ?? null)
            : ($resource->tenant_id ?? null);

        if ($resourceTenantId === null) {
            // Resources without a tenant_id are considered global/public.
            return true;
        }

        return (int) $user->tenant_id === (int) $resourceTenantId;
    }
}
