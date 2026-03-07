<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a cross-service operation fails.
 * Used to propagate errors from remote service calls so that
 * the local transaction can be rolled back (Saga pattern).
 */
class ServiceException extends Exception
{
    private int $serviceStatusCode;

    public function __construct(string $message = '', int $serviceStatusCode = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->serviceStatusCode = $serviceStatusCode;
    }

    /**
     * Get the HTTP status code returned by the remote service.
     */
    public function getServiceStatusCode(): int
    {
        return $this->serviceStatusCode;
    }
}
