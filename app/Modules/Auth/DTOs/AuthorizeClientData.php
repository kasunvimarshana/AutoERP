<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

final readonly class AuthorizeClientData
{
    /**
     * @param  list<string>  $scopes
     */
    public function __construct(
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public string $clientKey,
        public ?string $clientSecret,
        public ?int $userId,
        public ?int $identityId,
        public ?int $sessionId,
        public array $scopes,
        public ?string $redirectUri,
        public ?string $codeChallenge,
        public ?string $codeChallengeMethod,
        public int $ttlSeconds,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            (string) ($payload['client_key'] ?? ''),
            isset($payload['client_secret']) ? (string) $payload['client_secret'] : null,
            isset($payload['user_id']) ? (int) $payload['user_id'] : null,
            isset($payload['identity_id']) ? (int) $payload['identity_id'] : null,
            isset($payload['session_id']) ? (int) $payload['session_id'] : null,
            isset($payload['scopes']) && is_array($payload['scopes']) ? array_values($payload['scopes']) : [],
            isset($payload['redirect_uri']) ? (string) $payload['redirect_uri'] : null,
            isset($payload['code_challenge']) ? (string) $payload['code_challenge'] : null,
            isset($payload['code_challenge_method']) ? (string) $payload['code_challenge_method'] : null,
            max(30, (int) ($payload['ttl_seconds'] ?? 300)),
        );
    }
}
