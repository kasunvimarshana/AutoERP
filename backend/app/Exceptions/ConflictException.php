<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Conflict Exception
 * 
 * Thrown when a resource conflict occurs (e.g., duplicate entry)
 */
class ConflictException extends BusinessException
{
    protected int $statusCode = 409;
    protected string $errorCode = 'CONFLICT';

    public function __construct(string $message = "Resource conflict", array $context = [])
    {
        parent::__construct($message, $context);
    }
}
