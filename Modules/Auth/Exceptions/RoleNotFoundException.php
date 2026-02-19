<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

/**
 * Role Not Found Exception
 *
 * Thrown when a requested role cannot be found.
 */
class RoleNotFoundException extends NotFoundException
{
    protected string $errorCode = 'ROLE_NOT_FOUND';

    /**
     * Create a new role not found exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The requested role was not found.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
