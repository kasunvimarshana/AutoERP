<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base API Controller
 *
 * Provides standardized JSON response methods for all API controllers
 */
abstract class ApiController extends Controller
{
    /**
     * Return a success response
     */
    protected function success(
        mixed $data = null,
        string $message = '',
        int $status = Response::HTTP_OK
    ): JsonResponse {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Return an error response
     */
    protected function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        mixed $errors = null,
        ?string $code = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($code) {
            $response['code'] = $code;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a resource response
     */
    protected function resource(
        JsonResource $resource,
        int $status = Response::HTTP_OK
    ): JsonResponse {
        return $resource->response()->setStatusCode($status);
    }

    /**
     * Return a collection response
     */
    protected function collection(
        ResourceCollection $collection,
        int $status = Response::HTTP_OK
    ): JsonResponse {
        return $collection->response()->setStatusCode($status);
    }

    /**
     * Return a paginated response
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        ?string $resourceClass = null
    ): JsonResponse {
        $data = $paginator->items();

        if ($resourceClass) {
            $data = $resourceClass::collection($data);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Return a created response
     */
    protected function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return $this->success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return a no content response
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return a not found response
     */
    protected function notFound(
        string $message = 'Resource not found'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Return an unauthorized response
     */
    protected function unauthorized(
        string $message = 'Unauthorized'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return a forbidden response
     */
    protected function forbidden(
        string $message = 'Forbidden'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Return a validation error response
     */
    protected function validationError(
        mixed $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->error(
            $message,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $errors
        );
    }

    /**
     * Return a conflict response (e.g., optimistic locking failure)
     */
    protected function conflict(
        string $message = 'Resource has been modified by another user'
    ): JsonResponse {
        return $this->error($message, Response::HTTP_CONFLICT);
    }
}
