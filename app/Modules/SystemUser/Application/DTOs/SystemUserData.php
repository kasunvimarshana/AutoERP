<?php

declare(strict_types=1);

namespace Modules\SystemUser\Application\DTOs;

final readonly class SystemUserData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public ?int $userId = null,
        public ?string $code = null,
        public ?string $registrationNumber = null,
        public string $status = 'active',
        public ?string $notes = null,
        public ?int $createdBy = null,
        public ?int $updatedBy = null,
        public ?array $metadata = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int|string $tenantId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            code: $data['code'] ?? null,
            registrationNumber: $data['registration_number'] ?? null,
            status: (string) ($data['status'] ?? 'active'),
            notes: $data['notes'] ?? null,
            createdBy: isset($data['created_by']) ? (int) $data['created_by'] : null,
            updatedBy: isset($data['updated_by']) ? (int) $data['updated_by'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
