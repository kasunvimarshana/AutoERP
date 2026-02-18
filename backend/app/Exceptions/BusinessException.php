<?php

<<<<<<< HEAD
namespace App\Exceptions;

use Exception;

/**
 * Base exception for business logic errors
 */
class BusinessException extends Exception
{
    protected int $statusCode = 400;
    protected array $errors = [];

    public function __construct(
        string $message = 'Business logic error',
        int $statusCode = 400,
        array $errors = []
    ) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

=======
declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Base Business Exception
 * 
 * All business logic exceptions should extend this class.
 * Provides consistent error handling across the application.
 */
abstract class BusinessException extends Exception
{
    /**
     * HTTP status code for this exception
     */
    protected int $statusCode = 400;

    /**
     * Error code for client applications
     */
    protected string $errorCode = 'BUSINESS_ERROR';

    /**
     * Additional error context
     */
    protected array $context = [];

    public function __construct(string $message = "", array $context = [], ?Throwable $previous = null)
    {
        $this->context = $context;
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the HTTP status code
     */
>>>>>>> kv-erp-001
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

<<<<<<< HEAD
    public function getErrors(): array
    {
        return $this->errors;
=======
    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the error context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->message,
                'context' => $this->context,
            ],
        ], $this->statusCode);
>>>>>>> kv-erp-001
    }
}
