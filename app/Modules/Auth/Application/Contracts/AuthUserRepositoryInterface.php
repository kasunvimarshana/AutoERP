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
     * Get the email address of a user by their ID.
     * Used for dispatching auth events after login/logout.
     */
    public function getEmailById(int $userId): ?string;

    /**
     * Get the ID of a user by their email address.
     * Used for dispatching auth events after successful authentication.
     */
    public function getIdByEmail(string $email): ?int;

    /**
     * Get all roles (with their permissions) assigned to a user.
     *
     * Returns an array of roles, each with:
     *   [ 'name' => 'admin', 'permissions' => ['manage-users', 'view-reports'] ]
     */
    public function getRolesWithPermissions(int $userId): array;

    /**
     * Create a new user account and return the new user's ID.
     *
     * @param  array{
     *     tenant_id: int,
     *     email: string,
     *     first_name: string,
     *     last_name: string,
     *     password: string,
     *     phone?: string|null
     * }  $data
     */
    public function createUser(array $data): int;
}
