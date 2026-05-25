<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

final readonly class VerificationChallengeRequestData
{
    public function __construct(
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public ?int $providerId,
        public ?int $identityId,
        public ?int $userId,
        public string $channel,
        public string $target,
        public string $challengeType,
        public int $ttlSeconds,
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
            isset($payload['identity_id']) ? (int) $payload['identity_id'] : null,
            isset($payload['user_id']) ? (int) $payload['user_id'] : null,
            (string) ($payload['channel'] ?? 'email'),
            (string) ($payload['target'] ?? ''),
            (string) ($payload['challenge_type'] ?? 'otp'),
            max(30, (int) ($payload['ttl_seconds'] ?? 600)),
            isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : null,
        );
    }
}
