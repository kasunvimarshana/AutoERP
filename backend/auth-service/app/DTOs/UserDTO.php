<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly int $tenantId,
        public readonly array $roles = ['staff'],
        public readonly array $attributes = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            tenantId: $data['tenant_id'],
            roles: $data['roles'] ?? ['staff'],
            attributes: $data['attributes'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'tenant_id' => $this->tenantId,
            'roles' => $this->roles,
            'attributes' => $this->attributes,
        ];
    }
}
