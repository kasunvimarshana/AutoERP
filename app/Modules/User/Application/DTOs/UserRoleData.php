<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class UserRoleData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $userId,
        public int $roleId,
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
            roleId: (int) $data['role_id'],
            tenantId: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
