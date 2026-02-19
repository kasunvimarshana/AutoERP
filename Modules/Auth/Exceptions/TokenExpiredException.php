<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\AuthorizationException;

/**
 * Token Expired Exception
 *
 * Thrown when an authentication token has expired.
 */
class TokenExpiredException extends AuthorizationException
{
    protected int $httpStatusCode = 401;

    protected string $errorCode = 'TOKEN_EXPIRED';

    /**
     * Create a new token expired exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The authentication token has expired.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
