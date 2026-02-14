<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\ServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Base API Controller
 * 
 * Provides standard CRUD operations for API resources.
 * Controllers should remain thin and delegate all business logic to services.
 * Handles request validation, service orchestration, and response formatting.
 */
abstract class BaseApiController extends Controller
{
    /**
     * The service instance
     */
    protected ServiceInterface $service;

    /**
     * Resource name for responses
     */
    protected string $resourceName = 'resource';

    /**
     * Constructor
     *
     * @param ServiceInterface $service
     */
    public function __construct(ServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $config = $this->buildQueryConfig($request);
            
            if ($request->has('paginate') && $request->boolean('paginate', true)) {
                $perPage = (int) $request->input('per_page', 15);
                $data = $this->service->getPaginated($perPage, $config);
                
                return $this->paginatedResponse($data);
            }
            
            $data = $this->service->getAll($config);
            
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource
     *
     * @param int|string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int|string $id, Request $request): JsonResponse
    {
        try {
            $relations = $this->getRelationsFromRequest($request);
            $data = $this->service->getById($id, $relations);
            
            if (!$data) {
                return $this->errorResponse(
                    ucfirst($this->resourceName) . ' not found',
                    Response::HTTP_NOT_FOUND
                );
            }
            
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateStore($request);
            $data = $this->service->create($validatedData);
            
            return $this->successResponse(
                $data,
                ucfirst($this->resourceName) . ' created successfully',
                Response::HTTP_CREATED
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource
     *
     * @param int|string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(int|string $id, Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateUpdate($request, $id);
            $data = $this->service->update($id, $validatedData);
            
            return $this->successResponse(
                $data,
                ucfirst($this->resourceName) . ' updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy(int|string $id): JsonResponse
    {
        try {
            $this->service->delete($id);
            
            return $this->successResponse(
                null,
                ucfirst($this->resourceName) . ' deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bulk delete resources
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer|min:1'
            ]);
            
            $count = $this->service->bulkDelete($request->input('ids'));
            
            return $this->successResponse(
                ['deleted_count' => $count],
                "{$count} " . str($this->resourceName)->plural() . " deleted successfully"
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Build query configuration from request
     *
     * @param Request $request
     * @return array
     */
    protected function buildQueryConfig(Request $request): array
    {
        $config = [];
        
        // Sparse field selection
        if ($request->has('fields')) {
            $config['columns'] = explode(',', $request->input('fields'));
        }
        
        // Eager loading
        if ($request->has('with')) {
            $config['relations'] = $this->parseRelations($request->input('with'));
        }
        
        // Filters
        if ($request->has('filter')) {
            $config['filters'] = $this->parseFilters($request->input('filter'));
        }
        
        // Search
        if ($request->has('search')) {
            $config['search'] = [
                'query' => $request->input('search'),
                'fields' => $this->getSearchableFields()
            ];
        }
        
        // Sorting
        if ($request->has('sort')) {
            $config['sorts'] = $this->parseSorts($request->input('sort'));
        }
        
        return $config;
    }

    /**
     * Parse relations from request
     *
     * @param string|array $relations
     * @return array
     */
    protected function parseRelations(string|array $relations): array
    {
        if (is_array($relations)) {
            return $relations;
        }
        
        return explode(',', $relations);
    }

    /**
     * Parse filters from request
     *
     * @param array $filters
     * @return array
     */
    protected function parseFilters(array $filters): array
    {
        return $filters;
    }

    /**
     * Parse sorts from request
     *
     * @param string|array $sorts
     * @return array
     */
    protected function parseSorts(string|array $sorts): array
    {
        if (is_array($sorts)) {
            return $sorts;
        }
        
        $sortArray = [];
        $sortParts = explode(',', $sorts);
        
        foreach ($sortParts as $sort) {
            if (str_starts_with($sort, '-')) {
                $sortArray[substr($sort, 1)] = 'desc';
            } else {
                $sortArray[$sort] = 'asc';
            }
        }
        
        return $sortArray;
    }

    /**
     * Get relations from request
     *
     * @param Request $request
     * @return array
     */
    protected function getRelationsFromRequest(Request $request): array
    {
        if ($request->has('with')) {
            return $this->parseRelations($request->input('with'));
        }
        
        return [];
    }

    /**
     * Get searchable fields for this resource
     * Override in child classes
     *
     * @return array
     */
    protected function getSearchableFields(): array
    {
        return [];
    }

    /**
     * Validate store request
     * Override in child classes
     *
     * @param Request $request
     * @return array
     */
    abstract protected function validateStore(Request $request): array;

    /**
     * Validate update request
     * Override in child classes
     *
     * @param Request $request
     * @param int|string $id
     * @return array
     */
    abstract protected function validateUpdate(Request $request, int|string $id): array;

    /**
     * Success response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Paginated response
     *
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @return JsonResponse
     */
    protected function paginatedResponse($paginator): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total()
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl()
            ]
        ]);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     *
     * @param ValidationException $exception
     * @return JsonResponse
     */
    protected function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return $this->errorResponse(
            'Validation failed',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $exception->errors()
        );
    }
}
