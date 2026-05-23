<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class UserDeviceData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $userId,
        public string $deviceToken,
        public ?string $platform = null,
        public ?string $deviceName = null,
        public ?string $lastActiveAt = null,
        public ?int $tenantId = null,
        public ?int $organizationUnitId = null,
        public ?array $metadata = null,
    )
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            deviceToken: (string) $data['device_token'],
            platform: $data['platform'] ?? null,
            deviceName: $data['device_name'] ?? null,
            lastActiveAt: $data['last_active_at'] ?? null,
            tenantId: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
