<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

/**
 * User Not Found Exception
 *
 * Thrown when a requested user cannot be found.
 */
class UserNotFoundException extends NotFoundException
{
    protected string $errorCode = 'USER_NOT_FOUND';

    /**
     * Create a new user not found exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The requested user was not found.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
