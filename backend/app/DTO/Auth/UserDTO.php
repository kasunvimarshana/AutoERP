<?php

namespace App\DTO\Auth;

use App\Models\User;

/**
 * Data Transfer Object for User Response
 */
class UserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $tenantId,
        public readonly array $roles,
        public readonly array $permissions,
        public readonly ?string $emailVerifiedAt = null,
        public readonly ?string $createdAt = null
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            tenantId: $user->tenant_id,
            roles: $user->roles->pluck('name')->toArray(),
            permissions: $user->getAllPermissions()->pluck('name')->toArray(),
            emailVerifiedAt: $user->email_verified_at?->toIso8601String(),
            createdAt: $user->created_at?->toIso8601String()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'tenant_id' => $this->tenantId,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
            'email_verified_at' => $this->emailVerifiedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
