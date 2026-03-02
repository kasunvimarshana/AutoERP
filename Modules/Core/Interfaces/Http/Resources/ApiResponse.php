<?php

declare(strict_types=1);

namespace Modules\Core\Interfaces\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Standard API response envelope.
 *
 * Every API endpoint MUST return responses through this class to enforce
 * a consistent response structure across all modules.
 *
 * Success structure:
 * {
 *   "success": true,
 *   "data": { ... } | [ ... ],
 *   "message": "...",          // optional
 *   "meta": { ... }            // optional — pagination, etc.
 * }
 *
 * Error structure:
 * {
 *   "success": false,
 *   "message": "...",
 *   "errors": { ... }          // optional — validation errors
 * }
 */
final class ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * @param  mixed  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        string $message = '',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $payload = ['success' => true];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($message !== '') {
            $payload['message'] = $message;
        }

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Return a successful JSON response with paginated data.
     */
    public static function paginated(LengthAwarePaginator $paginator, string $message = ''): JsonResponse
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        return self::success($paginator->items(), $message, 200, $meta);
    }

    /**
     * Return a created (201) JSON response.
     *
     * @param  mixed  $data
     */
    public static function created(mixed $data = null, string $message = 'Resource created.'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Return a no-content (204) JSON response.
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return an error JSON response.
     *
     * @param  array<string, mixed>  $errors
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        array $errors = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if (! empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Return a 401 Unauthorized response.
     */
    public static function unauthorized(string $message = 'Unauthenticated.'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Return a 403 Forbidden response.
     */
    public static function forbidden(string $message = 'This action is unauthorized.'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Return a 404 Not Found response.
     */
    public static function notFound(string $message = 'Resource not found.'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Return a 422 Unprocessable Entity response with validation errors.
     *
     * @param  array<string, mixed>  $errors
     */
    public static function validationError(array $errors, string $message = 'The given data was invalid.'): JsonResponse
    {
        return self::error($message, 422, $errors);
    }

    /**
     * Return a 500 Internal Server Error response.
     */
    public static function serverError(string $message = 'An unexpected error occurred.'): JsonResponse
    {
        return self::error($message, 500);
    }
}
