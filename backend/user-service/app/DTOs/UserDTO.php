<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly int $tenantId,
        public readonly array $roles = ['staff'],
        public readonly array $attributes = [],
        public readonly bool $isActive = true,
        public readonly ?string $password = null,
        public readonly ?int $authUserId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            tenantId: $data['tenant_id'],
            roles: $data['roles'] ?? ['staff'],
            attributes: $data['attributes'] ?? [],
            isActive: $data['is_active'] ?? true,
            password: $data['password'] ?? null,
            authUserId: $data['auth_user_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'tenant_id' => $this->tenantId,
            'roles' => $this->roles,
            'attributes' => $this->attributes,
            'is_active' => $this->isActive,
        ];
        if ($this->password !== null) {
            $data['password'] = $this->password;
        }
        if ($this->authUserId !== null) {
            $data['auth_user_id'] = $this->authUserId;
        }
        return $data;
    }
}
