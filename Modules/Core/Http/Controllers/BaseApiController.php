<?php

declare(strict_types=1);

namespace Modules\Core\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Support\ApiResponse;

/**
 * Base API Controller
 *
 * Provides common functionality for all API controllers
 * Native Laravel implementation
 */
abstract class BaseApiController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Success response
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return ApiResponse::success($data, $message, $statusCode);
    }

    /**
     * Error response
     *
     * @param  array<string, mixed>|null  $errors
     */
    protected function error(
        string $message = 'An error occurred',
        int $statusCode = 400,
        ?array $errors = null
    ): JsonResponse {
        return ApiResponse::error($message, $statusCode, $errors);
    }

    /**
     * Created response
     */
    protected function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return ApiResponse::created($data, $message);
    }

    /**
     * No content response
     */
    protected function noContent(): JsonResponse
    {
        return ApiResponse::noContent();
    }

    /**
     * Not found response
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return ApiResponse::notFound($message);
    }

    /**
     * Forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return ApiResponse::forbidden($message);
    }

    /**
     * Validation error response
     *
     * @param  array<string, array<string>>  $errors
     */
    protected function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return ApiResponse::validationError($errors, $message);
    }
}
