<?php

namespace App\Modules\Auth\DTOs;

class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $tenant_id,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            tenant_id: $data['tenant_id'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
