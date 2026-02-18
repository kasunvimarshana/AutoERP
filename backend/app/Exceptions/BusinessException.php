<?php

namespace App\Exceptions;

use Exception;

/**
 * Base exception for business logic errors
 */
class BusinessException extends Exception
{
    protected int $statusCode = 400;
    protected array $errors = [];

    public function __construct(
        string $message = 'Business logic error',
        int $statusCode = 400,
        array $errors = []
    ) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
