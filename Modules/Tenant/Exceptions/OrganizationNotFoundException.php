<?php

declare(strict_types=1);

namespace Modules\Tenant\Exceptions;

use Modules\Core\Exceptions\NotFoundException;

/**
 * Organization Not Found Exception
 *
 * Thrown when a requested organization cannot be found.
 */
class OrganizationNotFoundException extends NotFoundException
{
    protected string $errorCode = 'ORGANIZATION_NOT_FOUND';

    /**
     * Create a new organization not found exception instance
     *
     * @param  string  $message  Exception message
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'The requested organization was not found.',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
