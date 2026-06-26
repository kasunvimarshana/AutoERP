<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\AuthTokenKeyPrefix;
use Modules\Auth\Enums\GrantType;
use Modules\Auth\Exceptions\AuthFailure;

final readonly class TokenService
{
    public function __construct(
        private TenantTokenService $tenantTokens,
        private PlatformTokenService $platformTokens,
    ) {}

    /** @param list<string> $scopes @return array<string, mixed> */
    public function issueTenantSessionTokens(
        int $tenantId,
        int $sessionId,
        int $userId,
        ?int $clientId,
        array $scopes,
        GrantType $grantType,
    ): array {
        return $this->tenantTokens->issueSessionTokens(
            $tenantId,
            $sessionId,
            $userId,
            $clientId,
            $scopes,
            $grantType,
        );
    }

    /** @return array<string, mixed> */
    public function issuePlatformSessionTokens(int $sessionId, int $operatorId): array
    {
        return $this->platformTokens->issueSessionTokens($sessionId, $operatorId);
    }

    /** @return array<string, mixed> */
    public function validateAccessToken(string $plainToken): array
    {
        $key = $this->tokenKey($plainToken);

        return match (true) {
            str_starts_with($key, AuthTokenKeyPrefix::TENANT_ACCESS) => $this->tenantTokens->validateAccessToken($plainToken),
            str_starts_with($key, AuthTokenKeyPrefix::PLATFORM_ACCESS) => $this->platformTokens->validateAccessToken($plainToken),
            default => throw $this->invalidToken(),
        };
    }

    /** @return array<string, mixed> */
    public function refreshTenant(string $plainRefreshToken): array
    {
        return $this->tenantTokens->refresh($plainRefreshToken);
    }

    /** @return array<string, mixed> */
    public function refreshPlatform(string $plainRefreshToken): array
    {
        return $this->platformTokens->refresh($plainRefreshToken);
    }

    public function revokeTenantSession(int $tenantId, int $sessionId, int $userId, string $reason): void
    {
        $this->tenantTokens->revokeSession($tenantId, $sessionId, $userId, $reason);
    }

    public function revokePlatformSession(int $sessionId, int $operatorId, string $reason): void
    {
        $this->platformTokens->revokeSession($sessionId, $operatorId, $reason);
    }

    private function tokenKey(string $plainToken): string
    {
        $parts = explode('.', trim($plainToken), 2);
        if (count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
            throw $this->invalidToken();
        }

        return $parts[0];
    }

    private function invalidToken(): AuthFailure
    {
        return new AuthFailure(AuthErrorCode::TOKEN_INVALID, 'The authentication token is invalid.', 401);
    }
}
