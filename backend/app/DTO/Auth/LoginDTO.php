<?php

namespace App\DTO\Auth;

/**
 * Data Transfer Object for Login Request
 */
class LoginDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $tenantId = null,
        public readonly bool $remember = false
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            tenantId: $data['tenant_id'] ?? null,
            remember: $data['remember'] ?? false
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'tenant_id' => $this->tenantId,
            'remember' => $this->remember,
        ];
    }
}
