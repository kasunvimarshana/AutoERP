<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

final readonly class RegisterCommand
{
    public function __construct(
        public int $tenantId,
        public int $organisationId,
        public string $name,
        public string $email,
        public string $password,
        public string $role = 'user',
    ) {}
}
