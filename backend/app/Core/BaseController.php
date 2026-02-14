<?php

namespace App\Core;

use App\DTOs\QueryConfig;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class BaseController
 * 
 * Base API controller providing consistent response formatting
 * and dynamic query parameter handling
 * 
 * @package App\Core
 */
abstract class BaseController extends Controller
{
    /**
     * Build QueryConfig from request
     *
     * @param Request $request
     * @return QueryConfig
     */
    protected function buildQueryConfig(Request $request): QueryConfig
    {
        return QueryConfig::fromRequest($request->all());
    }

    /**
     * Return success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return error response
     *
     * @param string $message
     * @param int $statusCode
     * @param array|null $errors
     * @return JsonResponse
     */
    protected function error(string $message, int $statusCode = 400, ?array $errors = null): JsonResponse
    {
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
     * Return resource response
     *
     * @param JsonResource $resource
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function resource(JsonResource $resource, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return $this->success($resource, $message, $statusCode);
    }

    /**
     * Return collection response
     *
     * @param ResourceCollection $collection
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function collection(ResourceCollection $collection, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return $this->success($collection, $message, $statusCode);
    }

    /**
     * Return created response
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Return no content response
     *
     * @return JsonResponse
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Return validation error response
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }
}
