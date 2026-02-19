<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\AuthorizationException;

/**
 * Invalid Credentials Exception
 *
 * Thrown when authentication credentials are invalid.
 */
class InvalidCredentialsException extends AuthorizationException
{
    protected int $httpStatusCode = 401;

    protected string $errorCode = 'INVALID_CREDENTIALS';

    /**
     * Create a new invalid credentials exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The provided credentials are invalid.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
