<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

final readonly class LinkExternalIdentityData
{
    public function __construct(
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public int $userId,
        public string $providerKey,
        public string $providerUserKey,
        public bool $isPrimary,
        public ?array $claims,
        public ?array $metadata,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            (int) ($payload['user_id'] ?? 0),
            (string) ($payload['provider_key'] ?? ''),
            (string) ($payload['provider_user_key'] ?? ''),
            (bool) ($payload['is_primary'] ?? false),
            isset($payload['claims']) && is_array($payload['claims']) ? $payload['claims'] : null,
            isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : null,
        );
    }
}
