<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\AuthorizationException;

/**
 * Token Invalid Exception
 *
 * Thrown when an authentication token is invalid or malformed.
 */
class TokenInvalidException extends AuthorizationException
{
    protected int $httpStatusCode = 401;

    protected string $errorCode = 'TOKEN_INVALID';

    /**
     * Create a new token invalid exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The authentication token is invalid.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
