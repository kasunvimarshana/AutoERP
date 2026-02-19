<?php

declare(strict_types=1);

namespace Modules\Core\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponse
{
    /**
     * Return a success response
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = Response::HTTP_OK,
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated success response
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ], $meta),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Return an error response
     */
    public static function error(
        string $message = 'Error',
        int $statusCode = Response::HTTP_BAD_REQUEST,
        ?string $errorCode = null,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        if (! empty($errors)) {
            $response['errors'] = $errors;
        }

        if (! empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
            errorCode: 'VALIDATION_ERROR',
            errors: $errors
        );
    }

    /**
     * Return a not found response
     */
    public static function notFound(
        string $message = 'Resource not found',
        ?string $errorCode = null
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: Response::HTTP_NOT_FOUND,
            errorCode: $errorCode ?? 'NOT_FOUND'
        );
    }

    /**
     * Return an unauthorized response
     */
    public static function unauthorized(
        string $message = 'Unauthorized',
        ?string $errorCode = null
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: Response::HTTP_UNAUTHORIZED,
            errorCode: $errorCode ?? 'UNAUTHORIZED'
        );
    }

    /**
     * Return a forbidden response
     */
    public static function forbidden(
        string $message = 'Forbidden',
        ?string $errorCode = null
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: Response::HTTP_FORBIDDEN,
            errorCode: $errorCode ?? 'FORBIDDEN'
        );
    }

    /**
     * Return a created response
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message,
            statusCode: Response::HTTP_CREATED
        );
    }

    /**
     * Return a no content response
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return a server error response
     */
    public static function serverError(
        string $message = 'Internal server error',
        ?string $errorCode = null
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
            errorCode: $errorCode ?? 'SERVER_ERROR'
        );
    }
}
