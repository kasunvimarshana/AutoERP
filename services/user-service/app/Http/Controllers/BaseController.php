<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    // -------------------------------------------------------------------------
    // Standard success response
    // -------------------------------------------------------------------------

    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    // -------------------------------------------------------------------------
    // Standard error response
    // -------------------------------------------------------------------------

    protected function errorResponse(
        string $message = 'An error occurred',
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $statusCode);
    }

    // -------------------------------------------------------------------------
    // Paginated response – wraps LengthAwarePaginator or plain Collection
    // -------------------------------------------------------------------------

    protected function paginatedResponse(
        mixed $paginator,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        // If it's a paginator, extract pagination meta
        if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return response()->json([
                'success'    => true,
                'message'    => $message,
                'data'       => $paginator->items(),
                'pagination' => [
                    'current_page'  => $paginator->currentPage(),
                    'last_page'     => $paginator->lastPage(),
                    'per_page'      => $paginator->perPage(),
                    'total'         => $paginator->total(),
                    'from'          => $paginator->firstItem(),
                    'to'            => $paginator->lastItem(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                ],
            ], $statusCode);
        }

        // Plain collection / array
        return response()->json([
            'success'    => true,
            'message'    => $message,
            'data'       => $paginator,
            'pagination' => null,
        ], $statusCode);
    }

    // -------------------------------------------------------------------------
    // 201 Created shorthand
    // -------------------------------------------------------------------------

    protected function createdResponse(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    // -------------------------------------------------------------------------
    // 404 Not Found shorthand
    // -------------------------------------------------------------------------

    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, null, 404);
    }

    // -------------------------------------------------------------------------
    // 422 Unprocessable Entity shorthand
    // -------------------------------------------------------------------------

    protected function validationErrorResponse(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, $errors, 422);
    }

    // -------------------------------------------------------------------------
    // 403 Forbidden shorthand
    // -------------------------------------------------------------------------

    protected function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, null, 403);
    }
}
