<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

final readonly class OrganizationUnitSettingGroupData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public int $organizationUnitId,
        public string $key,
        public ?string $value = null,
        public ?int $parentId = null,
        public ?array $metadata = null,
    ) {}

    public static function fromArray(int|string $tenantId, int|string $organizationUnitId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            organizationUnitId: (int) $organizationUnitId,
            key: (string) $data['key'],
            value: $data['value'] ?? null,
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
