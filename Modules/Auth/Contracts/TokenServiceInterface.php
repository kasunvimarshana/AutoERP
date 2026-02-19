<?php

declare(strict_types=1);

namespace Modules\Auth\Contracts;

/**
 * TokenServiceInterface
 *
 * Contract for JWT token generation and validation
 */
interface TokenServiceInterface
{
    /**
     * Generate a new token for user, device, and organization
     */
    public function generate(
        string $userId,
        string $deviceId,
        ?string $organizationId = null,
        ?string $tenantId = null,
        array $claims = []
    ): string;

    /**
     * Validate and parse a token
     *
     * @return array|null Token payload or null if invalid
     */
    public function validate(string $token): ?array;

    /**
     * Refresh an existing token
     */
    public function refresh(string $token): ?string;

    /**
     * Revoke a token
     */
    public function revoke(string $token): bool;

    /**
     * Check if a token is revoked
     */
    public function isRevoked(string $tokenId): bool;
}
