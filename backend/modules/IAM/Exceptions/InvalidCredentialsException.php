<?php

namespace Modules\IAM\Exceptions;

use Modules\Core\Exceptions\DomainException;

/**
 * Thrown when authentication credentials are invalid
 */
class InvalidCredentialsException extends DomainException
{
    protected int $statusCode = 401;

    public static function default(): self
    {
        return new self('The provided credentials are incorrect.');
    }

    public static function accountInactive(): self
    {
        return new self('Your account has been deactivated.');
    }

    public static function accountNotVerified(): self
    {
        return new self('Your account has not been verified. Please check your email.');
    }

    public static function invalidMfaCode(): self
    {
        return new self('The provided MFA code is invalid or expired.');
    }
}
