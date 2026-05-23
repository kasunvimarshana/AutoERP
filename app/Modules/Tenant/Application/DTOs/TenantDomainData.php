<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

final readonly class TenantDomainData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public string $domain,
        public bool $isPrimary = false,
        public bool $isVerified = false,
        public ?string $verifiedAt = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int|string $tenantId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            domain: (string) $data['domain'],
            isPrimary: (bool) ($data['is_primary'] ?? false),
            isVerified: (bool) ($data['is_verified'] ?? false),
            verifiedAt: $data['verified_at'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
