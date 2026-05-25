<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts\Providers;

use Modules\Auth\Application\DTOs\LoginData;
use Modules\Auth\Application\DTOs\RegistrationData;

interface AuthProviderInterface
{
    public function key(): string;

    /**
     * @return array{
     *     user: array<string, mixed>,
     *     provider: array<string, mixed>,
     *     identity: array<string, mixed>|null
     * }|null
     */
    public function authenticate(LoginData $data): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function register(RegistrationData $data): ?array;
}
