<?php

declare(strict_types=1);

namespace Modules\Tenant\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

/**
 * Tenant Not Found Exception
 *
 * Thrown when a requested tenant cannot be found.
 */
class TenantNotFoundException extends NotFoundException
{
    protected string $errorCode = 'TENANT_NOT_FOUND';

    /**
     * Create a new tenant not found exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The requested tenant was not found.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
