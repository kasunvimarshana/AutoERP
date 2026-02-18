<?php

namespace App\Exceptions;

class TenantNotFoundException extends BusinessException
{
    public function __construct(string $message = 'Tenant not found or inactive')
    {
        parent::__construct($message, 404);
    }
}
