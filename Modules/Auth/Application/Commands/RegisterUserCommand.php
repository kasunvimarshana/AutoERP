<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

final readonly class RegisterUserCommand
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}
