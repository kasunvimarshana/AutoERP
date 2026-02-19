<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

/**
 * Not Found Exception
 *
 * Thrown when a requested resource cannot be found.
 */
class NotFoundException extends DomainException
{
    protected int $httpStatusCode = 404;

    protected string $errorCode = 'NOT_FOUND';

    /**
     * Create a new not found exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The requested resource was not found.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
