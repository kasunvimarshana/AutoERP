<?php

namespace App\Modules\User\DTOs;

class UserDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $keycloak_id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $department,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            keycloak_id: $data['keycloak_id'],
            name: $data['name'],
            email: $data['email'],
            department: $data['department'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'keycloak_id' => $this->keycloak_id,
            'name' => $this->name,
            'email' => $this->email,
            'department' => $this->department,
        ];
    }
}
