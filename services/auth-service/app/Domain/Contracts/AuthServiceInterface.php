<?php

namespace App\Domain\Contracts;

use App\Domain\Models\User;

interface AuthServiceInterface
{
    /**
     * Authenticate user within a tenant context.
     *
     * @return array{user: User, access_token: string, refresh_token: string|null, expires_in: int}
     * @throws \App\Exceptions\AuthenticationException
     * @throws \App\Exceptions\TenantNotFoundException
     */
    public function login(
        string $email,
        string $password,
        ?string $tenantId = null,
        ?string $deviceId = null,
        ?string $deviceName = null,
        bool $rememberMe = false
    ): array;

    /**
     * Register a new user within a tenant.
     *
     * @return array{user: User, access_token: string, refresh_token: string|null, expires_in: int}
     * @throws \App\Exceptions\TenantNotFoundException
     */
    public function register(array $data): array;

    /**
     * Logout the authenticated user.
     */
    public function logout(User $user, ?string $deviceId = null, bool $revokeAll = false): void;

    /**
     * Refresh an access token using a refresh token.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     * @throws \App\Exceptions\InvalidTokenException
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * Send password reset link.
     */
    public function sendPasswordResetLink(string $email, ?string $tenantId = null): void;

    /**
     * Reset password using token.
     */
    public function resetPassword(
        string $token,
        string $email,
        string $password,
        ?string $tenantId = null
    ): void;

    /**
     * Get authenticated user by token.
     */
    public function getUserFromToken(string $token): ?User;
}
