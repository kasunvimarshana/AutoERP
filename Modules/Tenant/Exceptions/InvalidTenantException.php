<?php

declare(strict_types=1);

namespace Modules\Tenant\Exceptions;

use Modules\Core\Exceptions\ValidationException;

/**
 * Invalid Tenant Exception
 *
 * Thrown when tenant data is invalid or tenant is in an invalid state.
 */
class InvalidTenantException extends ValidationException
{
    protected string $errorCode = 'INVALID_TENANT';

    /**
     * Create a new invalid tenant exception instance
     *
     * @param  string  $message  Exception message
     * @param  array  $errors  Validation errors
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     */
    public function __construct(
        string $message = 'The tenant data is invalid.',
        array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $errors, $code, $previous);
    }
}
