<?php

declare(strict_types=1);

namespace Modules\Tenant\Exceptions;

use Modules\Core\Exceptions\BusinessRuleException;

/**
 * Circular Reference Exception
 *
 * Thrown when a circular reference is detected in the organization hierarchy.
 */
class CircularReferenceException extends BusinessRuleException
{
    protected string $errorCode = 'CIRCULAR_REFERENCE';

    /**
     * Create a new circular reference exception instance
     *
     * @param  string  $message  Exception message
     * @param  string|null  $ruleName  The name of the violated rule
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'Circular reference detected in organization hierarchy.',
        ?string $ruleName = 'organization_hierarchy',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $ruleName, $code, $previous, $context);
    }
}
