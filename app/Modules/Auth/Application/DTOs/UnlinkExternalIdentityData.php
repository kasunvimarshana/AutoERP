<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

final readonly class UnlinkExternalIdentityData
{
    public function __construct(
        public ?int $tenantId,
        public int $userId,
        public string $providerKey,
        public string $providerUserKey,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            (int) ($payload['user_id'] ?? 0),
            (string) ($payload['provider_key'] ?? ''),
            (string) ($payload['provider_user_key'] ?? ''),
        );
    }
}
