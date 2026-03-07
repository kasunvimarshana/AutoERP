<?php

namespace App\Exceptions;

use RuntimeException;

class AuthorizationException extends RuntimeException
{
    public function __construct(string $message = 'You are not authorized to perform this action.', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
