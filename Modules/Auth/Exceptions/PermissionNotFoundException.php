<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

/**
 * Permission Not Found Exception
 *
 * Thrown when a requested permission cannot be found.
 */
class PermissionNotFoundException extends NotFoundException
{
    protected string $errorCode = 'PERMISSION_NOT_FOUND';

    /**
     * Create a new permission not found exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The requested permission was not found.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
