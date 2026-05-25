<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

final readonly class TokenIssueData
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public ?int $providerId,
        public ?int $clientId,
        public ?int $identityId,
        public ?int $sessionId,
        public ?int $userId,
        public array $scopes,
        public string $grantType,
        public int $accessTokenTtlSeconds,
        public int $refreshTokenTtlSeconds,
        public ?array $metadata,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            isset($payload['provider_id']) ? (int) $payload['provider_id'] : null,
            isset($payload['client_id']) ? (int) $payload['client_id'] : null,
            isset($payload['identity_id']) ? (int) $payload['identity_id'] : null,
            isset($payload['session_id']) ? (int) $payload['session_id'] : null,
            isset($payload['user_id']) ? (int) $payload['user_id'] : null,
            isset($payload['scopes']) && is_array($payload['scopes']) ? array_values($payload['scopes']) : [],
            (string) ($payload['grant_type'] ?? 'password'),
            max(1, (int) ($payload['access_token_ttl_seconds'] ?? 3600)),
            max(1, (int) ($payload['refresh_token_ttl_seconds'] ?? 2592000)),
            isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : null,
        );
    }
}
