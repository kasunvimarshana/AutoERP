<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

/**
 * Auth Service Interface
 *
 * Defines the contract for authentication operations.
 */
interface AuthServiceInterface
{
    /**
     * Register a new user.
     */
    public function register(array $data): array;

    /**
     * Authenticate a user and return access token.
     */
    public function login(array $credentials): array;

    /**
     * Revoke user tokens (logout).
     */
    public function logout(int|string $userId): bool;

    /**
     * Refresh access token.
     */
    public function refreshToken(string $token): array;

    /**
     * Validate a token and return user data.
     */
    public function validateToken(string $token): ?array;

    /**
     * Assign a role to a user.
     */
    public function assignRole(int|string $userId, string $role): bool;

    /**
     * Check if user has permission.
     */
    public function hasPermission(int|string $userId, string $permission): bool;
}
