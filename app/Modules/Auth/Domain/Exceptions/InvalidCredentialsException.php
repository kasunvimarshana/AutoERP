<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

class InvalidCredentialsException extends AuthenticationException
{
    public function __construct(string $message = 'Invalid credentials provided', int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
