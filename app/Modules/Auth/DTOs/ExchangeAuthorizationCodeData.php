<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

final readonly class ExchangeAuthorizationCodeData
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public ?int $tenantId,
        public string $authorizationCode,
        public string $clientKey,
        public ?string $clientSecret,
        public ?string $redirectUri,
        public ?string $codeVerifier,
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
            (string) ($payload['authorization_code'] ?? ''),
            (string) ($payload['client_key'] ?? ''),
            isset($payload['client_secret']) ? (string) $payload['client_secret'] : null,
            isset($payload['redirect_uri']) ? (string) $payload['redirect_uri'] : null,
            isset($payload['code_verifier']) ? (string) $payload['code_verifier'] : null,
            isset($payload['scopes']) && is_array($payload['scopes']) ? array_values($payload['scopes']) : [],
            max(1, (int) ($payload['access_token_ttl_seconds'] ?? 3600)),
            max(1, (int) ($payload['refresh_token_ttl_seconds'] ?? 2592000)),
        );
    }
}
