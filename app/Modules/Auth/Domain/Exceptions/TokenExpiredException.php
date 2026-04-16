<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Exceptions;

class TokenExpiredException extends AuthenticationException
{
    public function __construct(string $message = 'Token has expired', int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
