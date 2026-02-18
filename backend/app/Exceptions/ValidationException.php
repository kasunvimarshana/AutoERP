<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Validation Exception
 * 
 * Thrown when business rule validation fails
 */
class ValidationException extends BusinessException
{
    protected int $statusCode = 422;
    protected string $errorCode = 'VALIDATION_ERROR';

    public function __construct(string $message = "Validation failed", array $errors = [])
    {
        parent::__construct($message, ['errors' => $errors]);
    }
}
