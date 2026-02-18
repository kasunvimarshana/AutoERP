<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * API Response Helper
 *
 * Provides consistent JSON response structure across the application
 * Native Laravel implementation - NO third-party packages
 */
class ApiResponse
{
    /**
     * Success response with data
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            // Handle Laravel resources
            if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
                return $data->additional(['success' => true, 'message' => $message])
                    ->response()
                    ->setStatusCode($statusCode);
            }

            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response
     *
     * @param  array<string, mixed>|null  $errors
     */
    public static function error(
        string $message = 'An error occurred',
        int $statusCode = 400,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     *
     * @param  array<string, array<string>>  $errors
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Not found response
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Server error response
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, 500);
    }

    /**
     * Created response (201)
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return self::success($data, $message, 201);
    }

    /**
     * Accepted response (202)
     */
    public static function accepted(string $message = 'Request accepted'): JsonResponse
    {
        return self::success(null, $message, 202);
    }

    /**
     * No content response (204)
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Paginated response
     */
    public static function paginated(
        ResourceCollection $collection,
        string $message = 'Success'
    ): JsonResponse {
        return $collection
            ->additional([
                'success' => true,
                'message' => $message,
            ])
            ->response();
    }

    /**
     * Custom response with meta data
     *
     * @param  array<string, mixed>  $meta
     */
    public static function withMeta(
        mixed $data,
        array $meta,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $statusCode);
    }
}
