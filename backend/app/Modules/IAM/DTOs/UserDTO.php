<?php

namespace App\Modules\IAM\DTOs;

/**
 * User Data Transfer Object
 *
 * Encapsulates data for user operations.
 */
class UserDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $password = null,
        public readonly bool $isActive = true,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            name: $data['name'],
            email: $data['email'],
            password: $data['password'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        $data = [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->isActive,
        ];

        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        return $data;
    }
}
