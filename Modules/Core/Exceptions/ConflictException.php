<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

/**
 * Conflict Exception
 *
 * Thrown when a request conflicts with the current state of the resource.
 * Examples: duplicate resources, concurrent updates, constraint violations.
 */
class ConflictException extends DomainException
{
    protected int $httpStatusCode = 409;

    protected string $errorCode = 'CONFLICT';

    /**
     * Create a new conflict exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The request conflicts with the current state of the resource.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
