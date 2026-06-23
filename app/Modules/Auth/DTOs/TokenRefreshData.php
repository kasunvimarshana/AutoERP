<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Modules\Auth\Constants\AuthTokenScope;

final readonly class TokenRefreshData
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public ?int $tenantId,
        public string $tokenScope,
        public string $refreshToken,
        public array $scopes,
        public int $accessTokenTtlSeconds,
        public int $refreshTokenTtlSeconds,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            AuthTokenScope::normalize((string) ($payload['token_scope'] ?? AuthTokenScope::TENANT)),
            (string) ($payload['refresh_token'] ?? ''),
            isset($payload['scopes']) && is_array($payload['scopes']) ? array_values($payload['scopes']) : [],
            max(1, (int) ($payload['access_token_ttl_seconds'] ?? 3600)),
            max(1, (int) ($payload['refresh_token_ttl_seconds'] ?? 2592000)),
        );
    }
}
