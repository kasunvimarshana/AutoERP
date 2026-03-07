<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(
        public readonly ?int    $tenantId,
        public readonly ?string $keycloakId,
        public readonly string  $name,
        public readonly string  $email,
        public readonly ?string $username,
        public readonly string  $role,
        public readonly string  $status = 'active',
        public readonly array   $profile = [],
        public readonly array   $permissions = [],
        public readonly array   $metadata = [],
        public readonly ?int    $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId:    $data['tenant_id']    ?? null,
            keycloakId:  $data['keycloak_id']  ?? null,
            name:        $data['name'],
            email:       $data['email'],
            username:    $data['username']     ?? null,
            role:        $data['role']         ?? 'viewer',
            status:      $data['status']       ?? 'active',
            profile:     $data['profile']      ?? [],
            permissions: $data['permissions']  ?? [],
            metadata:    $data['metadata']     ?? [],
            id:          $data['id']           ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id'          => $this->id,
            'tenant_id'   => $this->tenantId,
            'keycloak_id' => $this->keycloakId,
            'name'        => $this->name,
            'email'       => $this->email,
            'username'    => $this->username,
            'role'        => $this->role,
            'status'      => $this->status,
            'profile'     => $this->profile,
            'permissions' => $this->permissions,
            'metadata'    => $this->metadata,
        ], fn ($v) => $v !== null);
    }
}
