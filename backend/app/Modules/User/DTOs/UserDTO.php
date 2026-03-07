<?php

namespace App\Modules\User\DTOs;

use App\Modules\User\Models\User;

readonly class UserDTO
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $email,
        public string $role,
        public ?int $tenantId,
        public array $abacAttributes,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'],
            email: $data['email'],
            role: $data['role'] ?? 'staff',
            tenantId: $data['tenant_id'] ?? null,
            abacAttributes: $data['abac_attributes'] ?? [],
        );
    }

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            role: $user->role ?? 'staff',
            tenantId: $user->tenant_id,
            abacAttributes: $user->abac_attributes ?? [],
        );
    }
}
