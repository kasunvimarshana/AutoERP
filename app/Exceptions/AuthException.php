<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class AuthException extends RuntimeException
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid credentials.', 401);
    }

    public static function accountInactive(): self
    {
        return new self('Account is inactive or suspended.', 403);
    }

    public static function tokenRevoked(): self
    {
        return new self('Token has been revoked.', 401);
    }

    public static function tokenVersionMismatch(): self
    {
        return new self('Token is no longer valid. Please log in again.', 401);
    }
}
