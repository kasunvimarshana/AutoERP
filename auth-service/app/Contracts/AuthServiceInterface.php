<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Contract for authentication service operations.
 *
 * Defines the core authentication capabilities that any
 * concrete implementation must provide, enabling easy
 * swapping of authentication strategies without breaking
 * existing consumers.
 */
interface AuthServiceInterface
{
    /**
     * Register a new user within a specific tenant.
     *
     * @param  array<string, mixed>  $data  Validated registration payload.
     * @return array{user: User, token: string}
     */
    public function register(array $data): array;

    /**
     * Authenticate a user and return an access token.
     *
     * @param  array<string, string>  $credentials  Email and password.
     * @return array{token: string, token_type: string, expires_in: int}|null
     */
    public function login(array $credentials): ?array;

    /**
     * Revoke the current user's active access token.
     */
    public function logout(User $user): void;

    /**
     * Refresh an expired access token using a refresh token.
     *
     * @param  string  $refreshToken
     * @return array{token: string, token_type: string, expires_in: int}|null
     */
    public function refreshToken(string $refreshToken): ?array;

    /**
     * Retrieve the authenticated user from a Bearer token.
     */
    public function getUserFromToken(string $token): ?User;
}
