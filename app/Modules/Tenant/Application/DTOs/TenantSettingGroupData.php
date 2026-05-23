<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

final readonly class TenantSettingGroupData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public string $key,
        public ?string $value = null,
        public ?int $parentId = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int|string $tenantId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            key: (string) $data['key'],
            value: $data['value'] ?? null,
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
