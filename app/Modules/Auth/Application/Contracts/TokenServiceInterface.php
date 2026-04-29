<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\Auth\Domain\Entities\AccessToken;

/**
 * Contract for token issuance and revocation.
 * Implementations can swap Passport, JWT, or any other token strategy.
 */
interface TokenServiceInterface
{
    /**
     * Issue a new access token for the given user.
     */
    public function issueToken(int $userId, ?string $tokenName = null, array $scopes = []): AccessToken;

    /**
     * Revoke the current access token for the given user.
     */
    public function revokeCurrentToken(int $userId): bool;

    /**
     * Revoke all access tokens for the given user.
     */
    public function revokeAllTokens(int $userId): bool;
}
