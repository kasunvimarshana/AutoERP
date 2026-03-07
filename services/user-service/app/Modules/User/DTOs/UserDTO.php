<?php

namespace App\Modules\User\DTOs;

class UserDTO
{
    public function __construct(
        public readonly string $keycloakId,
        public readonly string $email,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly array $roles = [],
        public readonly string $status = 'active',
        public readonly ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            keycloakId: $data['keycloak_id'],
            email: $data['email'],
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            roles: $data['roles'] ?? [],
            status: $data['status'] ?? 'active',
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'keycloak_id' => $this->keycloakId,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'roles' => $this->roles,
            'status' => $this->status,
            'metadata' => $this->metadata,
        ];
    }
}
