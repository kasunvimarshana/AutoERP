<?php

declare(strict_types=1);

namespace Modules\Core\Exceptions;

/**
 * Validation Exception
 *
 * Thrown when input validation fails.
 */
class ValidationException extends DomainException
{
    protected int $httpStatusCode = 422;

    protected string $errorCode = 'VALIDATION_ERROR';

    /**
     * Validation errors
     */
    protected array $errors = [];

    /**
     * Create a new validation exception instance
     *
     * @param  string  $message  Exception message
     * @param  array  $errors  Validation errors
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     */
    public function __construct(
        string $message = 'The given data was invalid.',
        array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Set validation errors
     */
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }
}
