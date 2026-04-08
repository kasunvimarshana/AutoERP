<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

interface AuthorizationServiceInterface
{
    public function can(string $userId, string $ability, mixed $subject = null): bool;
}
