<?php

namespace App\Modules\IAM\DTOs;

/**
 * Role Assignment Data Transfer Object
 *
 * Encapsulates data for user-role assignment operations.
 */
class RoleAssignmentDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly array $roleIds,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            roleIds: $data['role_ids'] ?? [],
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'role_ids' => $this->roleIds,
        ];
    }
}
