<?php

namespace App\DTO\Auth;

/**
 * Data Transfer Object for Registration Request
 */
class RegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $tenantId = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            tenantId: $data['tenant_id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'tenant_id' => $this->tenantId,
        ];
    }
}
