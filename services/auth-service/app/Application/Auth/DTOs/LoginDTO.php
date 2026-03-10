<?php

declare(strict_types=1);

namespace App\Application\Auth\DTOs;

/**
 * LoginDTO — encapsulates login request data.
 */
final class LoginDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $tenantId,
        public readonly string $guardName  = 'api',
        public readonly bool   $rememberMe = false,
    ) {}

    /**
     * @param  array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            email:      $data['email'],
            password:   $data['password'],
            tenantId:   $data['tenant_id'],
            guardName:  $data['guard']       ?? 'api',
            rememberMe: (bool) ($data['remember_me'] ?? false),
        );
    }
}
