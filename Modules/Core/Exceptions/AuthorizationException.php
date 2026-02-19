<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

/**
 * Authorization Exception
 *
 * Thrown when a user attempts to access a resource without proper authorization.
 */
class AuthorizationException extends DomainException
{
    protected int $httpStatusCode = 403;

    protected string $errorCode = 'AUTHORIZATION_ERROR';

    /**
     * Create a new authorization exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'This action is unauthorized.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
