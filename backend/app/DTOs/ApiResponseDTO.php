<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * API Response DTO
 * 
 * Standardized API response structure for all endpoints
 */
class ApiResponseDTO extends BaseDTO
{
    public bool $success;
    public string $message;
    public mixed $data;
    public ?array $errors;
    public ?array $meta;
    public int $statusCode;

    /**
     * Create success response
     */
    public static function success(
        mixed $data,
        string $message = 'Operation successful',
        ?array $meta = null,
        int $statusCode = 200
    ): static {
        $dto = new static();
        $dto->success = true;
        $dto->message = $message;
        $dto->data = $data;
        $dto->errors = null;
        $dto->meta = $meta;
        $dto->statusCode = $statusCode;

        return $dto;
    }

    /**
     * Create error response
     */
    public static function error(
        string $message,
        ?array $errors = null,
        mixed $data = null,
        int $statusCode = 400
    ): static {
        $dto = new static();
        $dto->success = false;
        $dto->message = $message;
        $dto->data = $data;
        $dto->errors = $errors;
        $dto->meta = null;
        $dto->statusCode = $statusCode;

        return $dto;
    }

    /**
     * Create created response
     */
    public static function created(
        mixed $data,
        string $message = 'Resource created successfully'
    ): static {
        return static::success($data, $message, null, 201);
    }

    /**
     * Create no content response
     */
    public static function noContent(string $message = 'Operation successful'): static
    {
        return static::success(null, $message, null, 204);
    }

    /**
     * Create not found response
     */
    public static function notFound(string $message = 'Resource not found'): static
    {
        return static::error($message, null, null, 404);
    }

    /**
     * Create validation error response
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): static {
        return static::error($message, $errors, null, 422);
    }

    /**
     * Create unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return static::error($message, null, null, 401);
    }

    /**
     * Create forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): static
    {
        return static::error($message, null, null, 403);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $response = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        if ($this->meta !== null) {
            $response['meta'] = $this->meta;
        }

        return $response;
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
