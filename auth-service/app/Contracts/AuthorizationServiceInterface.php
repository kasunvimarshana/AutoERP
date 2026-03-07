<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * Contract for RBAC/ABAC authorization.
 *
 * Supports both Role-Based Access Control (RBAC) and
 * Attribute-Based Access Control (ABAC), allowing the
 * system to enforce fine-grained permissions across
 * all tenant-scoped resources.
 */
interface AuthorizationServiceInterface
{
    /**
     * Check whether a user has a specific permission.
     *
     * @param  User    $user        The subject.
     * @param  string  $permission  The permission slug (e.g. "inventory.create").
     * @param  array<string, mixed>  $context  ABAC context (resource attributes, environment).
     */
    public function can(User $user, string $permission, array $context = []): bool;

    /**
     * Assign a role to a user within a tenant scope.
     */
    public function assignRole(User $user, string $role, string $tenantId): void;

    /**
     * Revoke a role from a user.
     */
    public function revokeRole(User $user, string $role, string $tenantId): void;

    /**
     * Return all permissions for a given user.
     *
     * @return array<string>
     */
    public function getPermissions(User $user): array;

    /**
     * Evaluate ABAC policy for a resource action.
     *
     * @param  User    $user
     * @param  string  $action    Action being performed (e.g. "read", "write", "delete").
     * @param  string  $resource  Resource type (e.g. "product", "order").
     * @param  array<string, mixed>  $attributes  Resource-level attributes for policy evaluation.
     */
    public function evaluatePolicy(User $user, string $action, string $resource, array $attributes = []): bool;
}
