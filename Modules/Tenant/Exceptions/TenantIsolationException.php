<?php

declare(strict_types=1);

namespace Modules\Tenant\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

/**
 * Tenant Isolation Exception
 *
 * Thrown when tenant isolation boundaries are violated.
 */
class TenantIsolationException extends BusinessRuleException
{
    protected int $httpStatusCode = 403;

    protected string $errorCode = 'TENANT_ISOLATION_VIOLATION';

    /**
     * Create a new tenant isolation exception instance
     *
     * @param  string  $message  Exception message
     * @param  string|null  $ruleName  The name of the violated rule
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'Tenant isolation boundary violation.',
        ?string $ruleName = null,
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $ruleName, $code, $previous, $context);
    }
}
