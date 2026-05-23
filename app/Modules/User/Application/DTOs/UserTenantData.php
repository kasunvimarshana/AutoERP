<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class UserTenantData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public int $userId,
        public ?int $organizationUnitId = null,
        public ?int $roleId = null,
        public bool $isDefault = false,
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
            tenantId: (int) $data['tenant_id'],
            userId: (int) $data['user_id'],
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            roleId: isset($data['role_id']) ? (int) $data['role_id'] : null,
            isDefault: (bool) ($data['is_default'] ?? false),
            metadata: $data['metadata'] ?? null,
        );
    }
}
