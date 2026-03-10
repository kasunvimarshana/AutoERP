<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use RuntimeException;

class UserAlreadyExistsException extends RuntimeException
{
    public function __construct(string $email, string $tenantId)
    {
        parent::__construct("User [{$email}] already exists for tenant [{$tenantId}].", 409);
    }
}
