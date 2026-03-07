<?php

namespace App\Exceptions;

use RuntimeException;

class TenantException extends RuntimeException
{
    public function __construct(string $message = 'Tenant error', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
