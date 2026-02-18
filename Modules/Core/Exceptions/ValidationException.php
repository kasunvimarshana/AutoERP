<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

use Exception;

/**
 * Exception thrown when validation fails
 *
 * This is different from Laravel's validation exception
 * It's for domain-level validation failures
 */
class ValidationException extends Exception
{
    /**
     * Validation errors
     *
     * @var array<string, array<string>>
     */
    protected array $errors = [];

    /**
     * Create a new validation exception
     *
     * @param  array<string, array<string>>  $errors
     */
    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        ?\Throwable $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct($message, 422, $previous);
    }

    /**
     * Get validation errors
     *
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Create exception from errors array
     *
     * @param  array<string, array<string>>  $errors
     */
    public static function withErrors(array $errors, string $message = 'Validation failed'): static
    {
        return new static($message, $errors);
    }

    /**
     * Create exception for a single field
     */
    public static function forField(string $field, string $error): static
    {
        return new static(
            "Validation failed for {$field}",
            [$field => [$error]]
        );
    }
}
