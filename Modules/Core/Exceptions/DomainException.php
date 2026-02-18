<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Exception;

/**
 * Base exception for domain-level errors
 *
 * Domain exceptions represent violations of business rules or invariants
 * They should be caught and handled appropriately in the application layer
 */
class DomainException extends Exception
{
    /**
     * Create a new domain exception instance
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code
     * @param  \Throwable|null  $previous  Previous exception for exception chaining
     */
    public function __construct(
        string $message = 'Domain rule violation',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get additional context for the exception
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [];
    }

    /**
     * Create exception for business rule violation
     */
    public static function businessRuleViolation(string $rule, string $details = ''): static
    {
        $message = "Business rule violation: {$rule}";
        if ($details) {
            $message .= ". {$details}";
        }

        return new static($message);
    }

    /**
     * Create exception for invalid state
     */
    public static function invalidState(string $entity, string $state): static
    {
        return new static("Invalid state for {$entity}: {$state}");
    }

    /**
     * Create exception for invariant violation
     */
    public static function invariantViolation(string $invariant): static
    {
        return new static("Invariant violation: {$invariant}");
    }
}
