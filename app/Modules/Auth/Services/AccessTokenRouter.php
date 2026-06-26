<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Constants\AuthTokenKeyPrefix;
use Modules\Auth\Exceptions\AuthFailure;

/** Routes bearer-token validation by the cryptographic token key prefix. */
final readonly class AccessTokenRouter
{
    public function __construct(
        private TenantTokenService $tenantTokens,
        private PlatformTokenService $platformTokens,
    ) {}

    /** @return array<string, mixed> */
    public function validate(string $plainToken): array
    {
        $key = $this->tokenKey($plainToken);

        return match (true) {
            str_starts_with($key, AuthTokenKeyPrefix::TENANT_ACCESS) => $this->tenantTokens->validateAccessToken($plainToken),
            str_starts_with($key, AuthTokenKeyPrefix::PLATFORM_ACCESS) => $this->platformTokens->validateAccessToken($plainToken),
            default => throw $this->invalidToken(),
        };
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
