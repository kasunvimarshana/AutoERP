<?php

declare(strict_types=1);

namespace App\Domain\Events;

use App\Domain\Entities\User;

/**
 * User Logged In Domain Event
 */
class UserLoggedIn
{
    public function __construct(
        public readonly User $user,
        public readonly string $ipAddress,
        public readonly string $userAgent
    ) {}
}
