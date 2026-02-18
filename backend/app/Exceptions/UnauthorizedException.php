<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Unauthorized Exception
 * 
 * Thrown when a user is not authenticated
 */
class UnauthorizedException extends BusinessException
{
    protected int $statusCode = 401;
    protected string $errorCode = 'UNAUTHORIZED';

    public function __construct(string $message = "Authentication required")
    {
        parent::__construct($message);
    }
}
