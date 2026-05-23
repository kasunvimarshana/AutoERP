<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

final readonly class OrganizationUnitSettingData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public int $organizationUnitId,
        public int $groupId,
        public string $key,
        public ?string $value = null,
        public ?array $metadata = null,
    ) {}

    public static function fromArray(int|string $tenantId, int|string $organizationUnitId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            organizationUnitId: (int) $organizationUnitId,
            groupId: (int) $data['group_id'],
            key: (string) $data['key'],
            value: $data['value'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
