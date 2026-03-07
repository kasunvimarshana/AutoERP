<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuthorizationServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

/**
 * RBAC + ABAC authorization service.
 *
 * RBAC is handled by Spatie Permission (roles / permissions).
 * ABAC is layered on top: policies evaluate resource attributes
 * and user attributes to make fine-grained access decisions.
 *
 * Policy example:
 *   A user with role "warehouse_manager" can update an inventory
 *   item only if the item belongs to the same tenant as the user.
 */
final class AuthorizationService implements AuthorizationServiceInterface
{
    /**
     * {@inheritDoc}
     *
     * Combines RBAC (role-based permission check via Spatie) with
     * an optional ABAC layer evaluated against the supplied context.
     */
    public function can(User $user, string $permission, array $context = []): bool
    {
        // RBAC check
        if (!$user->hasPermissionTo($permission)) {
            return false;
        }

        // ABAC context evaluation (if context provided)
        if (!empty($context)) {
            return $this->evaluateAbacContext($user, $permission, $context);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function assignRole(User $user, string $role, string $tenantId): void
    {
        // Roles are tenant-scoped via a naming convention: "{role}:{tenantId}"
        $scopedRole = "{$role}:{$tenantId}";

        Role::firstOrCreate(
            ['name' => $scopedRole, 'guard_name' => 'api'],
        );

        $user->assignRole($scopedRole);

        Log::info('Role assigned', [
            'user_id'   => $user->id,
            'role'      => $scopedRole,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function revokeRole(User $user, string $role, string $tenantId): void
    {
        $scopedRole = "{$role}:{$tenantId}";
        $user->removeRole($scopedRole);

        Log::info('Role revoked', [
            'user_id'   => $user->id,
            'role'      => $scopedRole,
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getPermissions(User $user): array
    {
        return $user->getAllPermissions()
            ->pluck('name')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     *
     * Evaluates ABAC policy: checks that resource-level attributes
     * are compatible with the user's own attributes and roles.
     */
    public function evaluatePolicy(
        User   $user,
        string $action,
        string $resource,
        array  $attributes = []
    ): bool {
        // Tenant isolation: the resource must belong to the same tenant
        if (isset($attributes['tenant_id'])) {
            if ($attributes['tenant_id'] !== $user->tenant_id) {
                Log::warning('ABAC tenant isolation denied', [
                    'user_id'            => $user->id,
                    'resource_tenant_id' => $attributes['tenant_id'],
                    'user_tenant_id'     => $user->tenant_id,
                ]);
                return false;
            }
        }

        // Combine action + resource into a Spatie permission name
        $permission = "{$resource}.{$action}";

        return $this->can($user, $permission, $attributes);
    }

    // ──────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────

    /**
     * Evaluate ABAC context against user attributes.
     *
     * @param  array<string, mixed>  $context
     */
    private function evaluateAbacContext(User $user, string $permission, array $context): bool
    {
        $userAttributes = $user->attributes ?? [];

        // Enforce tenant isolation when context includes tenant_id
        if (isset($context['tenant_id']) && $context['tenant_id'] !== $user->tenant_id) {
            return false;
        }

        // Department-level attribute check (e.g. "department" must match)
        if (
            isset($context['required_department'], $userAttributes['department']) &&
            $context['required_department'] !== $userAttributes['department']
        ) {
            return false;
        }

        return true;
    }
}
