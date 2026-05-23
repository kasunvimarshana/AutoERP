<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class PermissionData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $name,
        public ?string $guardName = null,
        public ?string $module = null,
        public ?string $description = null,
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
            name: (string) $data['name'],
            guardName: $data['guard_name'] ?? null,
            module: $data['module'] ?? null,
            description: $data['description'] ?? null,
            tenantId: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
