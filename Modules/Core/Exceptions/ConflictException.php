<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Exception;

/**
 * Exception thrown when a conflict occurs (e.g., duplicate resource)
 */
class ConflictException extends Exception
{
    /**
     * Create a new conflict exception
     */
    public function __construct(
        string $message = 'Resource conflict',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 409, $previous);
    }

    /**
     * Create exception for duplicate resource
     */
    public static function duplicate(string $resourceType, string $field, string|int $value): static
    {
        return new static(
            "{$resourceType} with {$field} '{$value}' already exists"
        );
    }

    /**
     * Create exception for resource already in use
     */
    public static function inUse(string $resourceType, string|int $identifier): static
    {
        return new static(
            "{$resourceType} '{$identifier}' is currently in use and cannot be modified"
        );
    }

    /**
     * Create exception for state conflict
     */
    public static function stateConflict(
        string $resource,
        string $currentState,
        string $requiredState
    ): static {
        return new static(
            "{$resource} is in '{$currentState}' state, but '{$requiredState}' is required"
        );
    }
}
