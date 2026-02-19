<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Exception
 *
 * Thrown when tenant-related operations fail
 */
class TenantException extends BaseException
{
    /**
     * @var int HTTP status code
     */
    protected int $statusCode = Response::HTTP_BAD_REQUEST;

    /**
     * Create exception for tenant not found
     */
    public static function notFound(string $identifier): static
    {
        $exception = new static("Tenant not found: $identifier");
        $exception->statusCode = Response::HTTP_NOT_FOUND;
        $exception->errorData = ['identifier' => $identifier];

        return $exception;
    }

    /**
     * Create exception for missing tenant context
     */
    public static function missingContext(): static
    {
        $exception = new static('Tenant context is required but not set');
        $exception->statusCode = Response::HTTP_BAD_REQUEST;

        return $exception;
    }

    /**
     * Create exception for tenant isolation violation
     */
    public static function isolationViolation(string $resource): static
    {
        $exception = new static("Tenant isolation violation: accessing $resource from different tenant");
        $exception->statusCode = Response::HTTP_FORBIDDEN;
        $exception->errorData = ['resource' => $resource];

        return $exception;
    }

    /**
     * Create exception for tenant initialization failure
     */
    public static function initializationFailed(string $reason = ''): static
    {
        $message = 'Failed to initialize tenant';
        if ($reason) {
            $message .= ": $reason";
        }

        return new static($message);
    }
}
