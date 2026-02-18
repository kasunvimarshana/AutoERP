<?php

namespace Modules\Core\Exceptions;

/**
 * Thrown when user lacks required permissions
 */
class UnauthorizedException extends DomainException
{
    protected int $statusCode = 403;

    public static function forAction(string $action, ?string $resource = null): self
    {
        $message = $resource 
            ? "Unauthorized to {$action} {$resource}"
            : "Unauthorized to perform action: {$action}";

        return new self(
            $message,
            ['action' => $action, 'resource' => $resource]
        );
    }

    public static function forPermission(string $permission): self
    {
        return new self(
            "Missing required permission: {$permission}",
            ['permission' => $permission]
        );
    }
}
