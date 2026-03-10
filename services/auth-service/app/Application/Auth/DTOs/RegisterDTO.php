<?php

declare(strict_types=1);

namespace App\Application\Auth\DTOs;

/**
 * RegisterDTO — encapsulates user registration request data.
 */
final class RegisterDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $email,
        public readonly string  $password,
        public readonly string  $tenantId,
        public readonly ?string $role      = null,
        public readonly array   $metadata  = [],
    ) {}

    /**
     * @param  array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            name:     $data['name'],
            email:    $data['email'],
            password: $data['password'],
            tenantId: $data['tenant_id'],
            role:     $data['role']     ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }
}
