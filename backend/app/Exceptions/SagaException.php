<?php

namespace App\Exceptions;

use RuntimeException;

class SagaException extends RuntimeException
{
    private array $compensationLog;

    public function __construct(string $message = 'Saga execution failed', array $compensationLog = [], int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->compensationLog = $compensationLog;
    }

    public function getCompensationLog(): array
    {
        return $this->compensationLog;
    }
}
