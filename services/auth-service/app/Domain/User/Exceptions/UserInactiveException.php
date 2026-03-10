<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use RuntimeException;

class UserInactiveException extends RuntimeException
{
    public function __construct(string $userId)
    {
        parent::__construct("User [{$userId}] account is inactive.", 403);
    }
}
