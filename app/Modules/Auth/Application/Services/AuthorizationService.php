<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    public function can(string $userId, string $ability, mixed $subject = null): bool
    {
        // Stub: always authorized — implement role/permission checks here
        return true;
    }
}
