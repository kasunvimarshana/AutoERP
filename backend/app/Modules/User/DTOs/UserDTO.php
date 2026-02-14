<?php

namespace App\Modules\User\DTOs;

class UserDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $tenant_id = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $phone = null,
        public ?string $status = 'active',
        public ?string $role = null,
        public ?array $permissions = [],
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            tenant_id: $data['tenant_id'] ?? null,
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
            phone: $data['phone'] ?? null,
            status: $data['status'] ?? 'active',
            role: $data['role'] ?? null,
            permissions: $data['permissions'] ?? [],
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'phone' => $this->phone,
            'status' => $this->status,
        ], fn($value) => !is_null($value));
    }
}
