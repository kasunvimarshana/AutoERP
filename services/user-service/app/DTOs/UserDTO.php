<?php

namespace App\DTOs;

/**
 * Data Transfer Object for creating / updating a user.
 */
final class UserDTO
{
    public function __construct(
        public readonly string      $name,
        public readonly string      $email,
        public readonly string      $password,
        public readonly ?int        $tenantId  = null,
        public readonly array       $roleIds   = [],
        public readonly bool        $isActive  = true,
        public readonly array       $metadata  = [],
        public readonly ?string     $ssoProvider = null,
        public readonly ?string     $ssoId       = null,
    ) {}

    // -------------------------------------------------------------------------
    // Factory methods
    // -------------------------------------------------------------------------

    public static function fromArray(array $data): self
    {
        return new self(
            name:        $data['name'],
            email:       $data['email'],
            password:    $data['password']   ?? '',
            tenantId:    isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            roleIds:     $data['role_ids']   ?? [],
            isActive:    isset($data['is_active']) ? (bool) $data['is_active'] : true,
            metadata:    $data['metadata']   ?? [],
            ssoProvider: $data['sso_provider'] ?? null,
            ssoId:       $data['sso_id']       ?? null,
        );
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    /**
     * Convert to array suitable for Eloquent create/fill.
     */
    public function toArray(): array
    {
        $data = [
            'name'      => $this->name,
            'email'     => $this->email,
            'password'  => $this->password,
            'is_active' => $this->isActive,
            'metadata'  => $this->metadata,
        ];

        if ($this->tenantId !== null) {
            $data['tenant_id'] = $this->tenantId;
        }

        if ($this->ssoProvider !== null) {
            $data['sso_provider'] = $this->ssoProvider;
        }

        if ($this->ssoId !== null) {
            $data['sso_id'] = $this->ssoId;
        }

        return $data;
    }
}
