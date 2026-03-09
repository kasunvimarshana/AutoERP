<?php

namespace App\Domain\Contracts;

use App\Domain\Models\User;
use Laravel\Passport\Token;

interface TokenRepositoryInterface
{
    /**
     * Create a personal access token for the user.
     *
     * @return array{access_token: string, refresh_token: string|null, expires_in: int, token_model: Token}
     */
    public function createForUser(
        User $user,
        string $tokenName,
        array $scopes = [],
        array $claims = []
    ): array;

    /**
     * Revoke a specific token by ID.
     */
    public function revoke(string $tokenId): bool;

    /**
     * Revoke all tokens for a user, optionally for a specific device.
     */
    public function revokeAllForUser(string $userId, ?string $deviceId = null): int;

    /**
     * Find a valid token by its plain-text value.
     */
    public function findValid(string $token): ?Token;

    /**
     * Get all active tokens for a user.
     */
    public function getActiveForUser(string $userId): \Illuminate\Support\Collection;

    /**
     * Prune expired tokens.
     */
    public function pruneExpired(): int;
}
