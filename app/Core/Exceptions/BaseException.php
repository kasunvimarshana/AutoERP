<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base Application Exception
 * 
 * Base class for all custom exceptions in the application
 */
abstract class BaseException extends Exception
{
    /**
     * @var int HTTP status code
     */
    protected int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * @var array<string, mixed> Additional error data
     */
    protected array $errorData = [];

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get error data
     *
     * @return array<string, mixed>
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }

    /**
     * Set error data
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public function setErrorData(array $data): self
    {
        $this->errorData = $data;
        return $this;
    }

    /**
     * Convert exception to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'errors' => $this->errorData,
        ];
    }
}
