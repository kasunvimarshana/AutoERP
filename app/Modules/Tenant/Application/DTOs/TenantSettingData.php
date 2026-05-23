<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

final readonly class TenantSettingData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public int $groupId,
        public string $key,
        public ?string $value = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int|string $tenantId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            groupId: (int) $data['group_id'],
            key: (string) $data['key'],
            value: $data['value'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
