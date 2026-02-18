<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Tenant Exception
 * 
 * Thrown when tenant-related operations fail
 */
class TenantException extends BusinessException
{
    protected int $statusCode = 400;
    protected string $errorCode = 'TENANT_ERROR';

    public function __construct(string $message = "Tenant operation failed", array $context = [])
    {
        parent::__construct($message, $context);
    }
}
