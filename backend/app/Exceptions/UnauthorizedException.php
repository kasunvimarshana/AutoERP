<?php

<<<<<<< HEAD
namespace App\Exceptions;

class UnauthorizedException extends BusinessException
{
    public function __construct(string $message = 'Unauthorized action')
    {
        parent::__construct($message, 403);
=======
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Unauthorized Exception
 * 
 * Thrown when a user is not authenticated
 */
class UnauthorizedException extends BusinessException
{
    protected int $statusCode = 401;
    protected string $errorCode = 'UNAUTHORIZED';

    public function __construct(string $message = "Authentication required")
    {
        parent::__construct($message);
>>>>>>> kv-erp-001
    }
}
