<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;

/**
 * Minimal repository contract for user lookups needed by the Auth module.
 *
 * This interface decouples auth services from the concrete UserModel,
 * keeping the Auth module independent of persistence implementation details.
 */
interface AuthUserRepositoryInterface
{
    /**
     * Find a user as OAuthenticatable for Passport token operations
     * (createToken, currentAccessToken, tokens, etc.).
     */
    public function findForPassport(int $userId): ?OAuthenticatable;

    /**
     * Find a user as Authenticatable for Gate/policy-based authorization (ABAC).
     */
    public function findAuthenticatable(int $userId): ?Authenticatable;

    /**
     * Get all roles (with their permissions) assigned to a user.
     *
     * Returns an array of roles, each with:
     *   [ 'name' => 'admin', 'permissions' => ['manage-users', 'view-reports'] ]
     */
    public function getRolesWithPermissions(int $userId): array;

    /**
     * Determine if a user has the given role.
     */
    public function hasRole(int $userId, string $role): bool;

    /**
     * Determine if a user has the given permission through assigned roles.
     */
    public function hasPermission(int $userId, string $permission): bool;
}
