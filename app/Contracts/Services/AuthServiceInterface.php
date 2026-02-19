<?php

namespace App\Contracts\Services;

interface AuthServiceInterface
{
    public function login(array $credentials, string $guardName = 'api'): array;

    public function logout(string $guardName = 'api'): void;

    public function refresh(string $guardName = 'api'): array;

    public function me(string $guardName = 'api'): array;
}
