<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\Auth\Application\DTOs\LoginData;
use Modules\Auth\Application\DTOs\RegisterUserData;

interface AuthServiceInterface
{
    /**
     * Register a new user and issue a Passport access token.
     *
     * @return array{user: mixed, token: string}
     */
    public function register(RegisterUserData $dto): array;

    /**
     * Authenticate a user with credentials and issue a Passport access token.
     *
     * @return array{user: mixed, token: string}
     */
    public function login(LoginData $dto): array;

    /**
     * Revoke all Passport tokens for the given user (logout).
     */
    public function logout(int $userId): void;

    /**
     * Exchange a refresh token for a new access token.
     *
     * @return array{access_token: string, expires_in: int}
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * Return the authenticated user record by ID.
     */
    public function me(int $userId): mixed;
}
