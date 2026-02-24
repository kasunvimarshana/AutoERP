<?php
namespace Modules\Auth\Domain\ValueObjects;
final class LoginCredentials
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $deviceName = null,
    ) {}
}
