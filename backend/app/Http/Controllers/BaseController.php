<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Base API Controller
 *
 * Provides standardized JSON response methods for consistent API responses.
 */
abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return a success response
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operation successful',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Return an error response
     */
    protected function errorResponse(
        string $message = 'Operation failed',
        mixed $errors = null,
        int $statusCode = 400
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
     * Return a created response
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a no content response
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a not found response
     */
    protected function notFoundResponse(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->errorResponse($message, null, 404);
    }

    /**
     * Return an unauthorized response
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->errorResponse($message, null, 401);
    }

    /**
     * Return a forbidden response
     */
    protected function forbiddenResponse(
        string $message = 'Forbidden'
    ): JsonResponse {
        return $this->errorResponse($message, null, 403);
    }

    /**
     * Return a validation error response
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->errorResponse($message, $errors, 422);
    }

    /**
     * Alias for successResponse for backwards compatibility
     *
     * @deprecated Use successResponse instead
     */
    protected function success($data = null, string $message = 'Operation successful', int $statusCode = 200): JsonResponse
    {
        return $this->successResponse($data, $message, $statusCode);
    }

    /**
     * Alias for errorResponse for backwards compatibility
     *
     * @deprecated Use errorResponse instead
     */
    protected function error(string $message = 'Operation failed', mixed $errors = null, int $statusCode = 400): JsonResponse
    {
        return $this->errorResponse($message, $errors, $statusCode);
    }

    /**
     * Alias for validationErrorResponse for backwards compatibility
     *
     * @deprecated Use validationErrorResponse instead
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->validationErrorResponse($errors, $message);
    }
}
