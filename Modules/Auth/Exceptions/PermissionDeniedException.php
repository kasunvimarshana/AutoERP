<?php

declare(strict_types=1);

namespace Modules\Auth\Exceptions;

use Modules\Core\Exceptions\AuthorizationException;

/**
 * Permission Denied Exception
 *
 * Thrown when a user attempts an action they don't have permission for.
 */
class PermissionDeniedException extends AuthorizationException
{
    protected string $errorCode = 'PERMISSION_DENIED';

    /**
     * The required permission
     */
    protected ?string $permission = null;

    /**
     * Create a new permission denied exception instance
     *
     * @param  string  $message  Exception message
     * @param  string|null  $permission  The required permission
     * @param  int  $code  Exception code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array  $context  Additional context data
     */
    public function __construct(
        string $message = 'You do not have permission to perform this action.',
        ?string $permission = null,
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->permission = $permission;
    }

    /**
     * Get the required permission
     */
    public function getPermission(): ?string
    {
        return $this->permission;
    }

    /**
     * Set the required permission
     */
    public function setPermission(string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }
}
