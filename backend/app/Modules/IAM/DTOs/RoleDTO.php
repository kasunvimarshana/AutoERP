<?php

namespace App\Modules\IAM\DTOs;

/**
 * Role Data Transfer Object
 *
 * Encapsulates data for role operations.
 */
class RoleDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description = null,
        public readonly bool $isSystemRole = false,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            isSystemRole: $data['is_system_role'] ?? false,
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_system_role' => $this->isSystemRole,
        ];
    }
}
