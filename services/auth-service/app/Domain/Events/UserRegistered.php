<?php

declare(strict_types=1);

namespace App\Domain\Events;

use App\Domain\Entities\User;

/**
 * User Registered Domain Event
 */
class UserRegistered
{
    public function __construct(
        public readonly User $user,
        public readonly int|string $tenantId
    ) {}
}
