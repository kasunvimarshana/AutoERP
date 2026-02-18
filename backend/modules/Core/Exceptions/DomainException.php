<?php

namespace Modules\Core\Exceptions;

use Exception;

/**
 * Base Domain Exception for all business logic errors
 * Extends this for specific domain exceptions
 */
abstract class DomainException extends Exception
{
    protected int $statusCode = 400;

    protected array $context = [];

    public function __construct(string $message = '', array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $this->statusCode, $previous);
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'error' => $this->getMessage(),
            'code' => $this->getCode(),
            'status' => $this->statusCode,
            'context' => $this->context,
        ];
    }
}
