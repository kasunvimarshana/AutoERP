<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Commands;

final readonly class LoginCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName = 'default',
    ) {}
}
