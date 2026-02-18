<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Forbidden Exception
 * 
 * Thrown when a user is authenticated but lacks permissions
 */
class ForbiddenException extends BusinessException
{
    protected int $statusCode = 403;
    protected string $errorCode = 'FORBIDDEN';

    public function __construct(string $resource = "", string $action = "")
    {
        $message = $resource && $action 
            ? "You do not have permission to {$action} {$resource}"
            : "You do not have permission to perform this action";
            
        parent::__construct($message, [
            'resource' => $resource,
            'action' => $action,
        ]);
    }
}
