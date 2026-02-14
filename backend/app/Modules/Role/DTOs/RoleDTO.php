<?php

namespace App\Modules\Role\DTOs;

class RoleDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $guard_name = 'web',
        public ?string $tenant_id = null,
        public ?array $permissions = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? null,
            guard_name: $data['guard_name'] ?? 'web',
            tenant_id: $data['tenant_id'] ?? null,
            permissions: $data['permissions'] ?? [],
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'tenant_id' => $this->tenant_id,
        ], fn($value) => !is_null($value));
    }
}
