<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

interface AuthorizationServiceInterface
{
    /**
     * Determine whether the given user has the specified ability/permission.
     *
     * @param  int    $userId   The authenticated user's ID.
     * @param  string $ability  Permission slug, e.g. 'products.view'.
     * @param  mixed  $subject  Optional resource the permission applies to.
     */
    public function can(int $userId, string $ability, mixed $subject = null): bool;

    /**
     * Assign a role to a user, optionally scoped to a tenant.
     */
    public function assignRole(int $userId, int $roleId, ?int $tenantId = null): void;

    /**
     * Revoke a role from a user, optionally scoped to a tenant.
     */
    public function revokeRole(int $userId, int $roleId, ?int $tenantId = null): void;

    /**
     * Replace all permissions on a role with the given set.
     *
     * @param  int[]  $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): void;

    /**
     * Return all permission slugs granted to a user (via all assigned roles).
     *
     * @return string[]
     */
    public function getUserPermissions(int $userId): array;
}
