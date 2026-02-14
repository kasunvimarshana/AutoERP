<?php

namespace App\Core\Http\Controllers;

use App\Core\Services\BaseService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Base CRUD Controller
 *
 * Provides a complete, production-ready CRUD implementation with:
 * - Advanced filtering (field-level and global search)
 * - Multi-field sorting
 * - Sparse field selection
 * - Configurable eager loading of relations
 * - Pagination
 * - Tenant-aware operations
 * - Consistent error handling
 * - Structured JSON responses
 *
 * To use, extend this class and implement:
 * - getQueryConfig() - Define filters, sorts, includes, etc.
 * - getValidationRules() - Define create/update validation rules
 */
abstract class BaseCrudController extends Controller
{
    /**
     * The service instance.
     */
    protected BaseService $service;

    /**
     * BaseCrudController constructor.
     */
    public function __construct(BaseService $service)
    {
        $this->service = $service;
    }

    /**
     * Get query configuration for advanced filtering.
     *
     * Override this method to configure allowed filters, sorts, includes, etc.
     *
     * Example:
     * return [
     *     'allowedFilters' => [
     *         'name',
     *         'email',
     *         AllowedFilter::exact('status'),
     *         AllowedFilter::scope('active'),
     *         AllowedFilter::callback('min_price', fn($query, $value) => $query->where('price', '>=', $value)),
     *     ],
     *     'allowedSorts' => ['name', 'created_at', 'updated_at'],
     *     'allowedIncludes' => ['organization', 'branch', 'contacts'],
     *     'allowedFields' => ['customers' => ['id', 'name', 'email'], 'organizations' => ['id', 'name']],
     *     'defaultSort' => '-created_at',
     *     'perPage' => 15,
     *     'globalSearch' => ['name', 'email', 'phone'],
     * ];
     */
    abstract protected function getQueryConfig(): array;

    /**
     * Get validation rules for create and update operations.
     *
     * Override this method to define validation rules.
     *
     * Example:
     * return [
     *     'create' => [
     *         'name' => 'required|string|max:255',
     *         'email' => 'required|email|unique:customers,email',
     *     ],
     *     'update' => [
     *         'name' => 'sometimes|string|max:255',
     *         'email' => 'sometimes|email|unique:customers,email,' . $id,
     *     ],
     * ];
     *
     * @param  string  $action  'create' or 'update'
     * @param  mixed  $id  Resource ID for update operations
     */
    abstract protected function getValidationRules(string $action, $id = null): array;

    /**
     * Display a listing of the resource.
     *
     * Supports:
     * - ?filter[field]=value - Field-level filtering
     * - ?search=term - Global search across configured fields
     * - ?sort=field,-other_field - Multi-field sorting (- for descending)
     * - ?include=relation1,relation2 - Eager load relations
     * - ?fields[table]=field1,field2 - Sparse fieldsets
     * - ?page[number]=1&page[size]=20 - Pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $config = $this->getQueryConfig();
            $data = $this->service->repository->paginateWithFilters($config);

            return $this->successResponse($data, 'Resources retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateRequest($request, 'create');
            $resource = $this->service->repository->create($validated);

            return $this->successResponse($resource, 'Resource created successfully', 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $config = $this->getQueryConfig();

            // Build query with includes if specified
            $query = $this->service->repository->queryWithFilters($config);
            $resource = $query->findOrFail($id);

            return $this->successResponse($resource, 'Resource retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Resource not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $this->validateRequest($request, 'update', $id);
            $success = $this->service->repository->update($id, $validated);

            if ($success) {
                $resource = $this->service->repository->findOrFail($id);

                return $this->successResponse($resource, 'Resource updated successfully');
            }

            return $this->errorResponse('Failed to update resource', 500);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Resource not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $success = $this->service->repository->delete($id);

            if ($success) {
                return $this->successResponse(null, 'Resource deleted successfully');
            }

            return $this->errorResponse('Failed to delete resource', 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Resource not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Validate the request data.
     *
     * @param  mixed  $id
     *
     * @throws ValidationException
     */
    protected function validateRequest(Request $request, string $action, $id = null): array
    {
        $rules = $this->getValidationRules($action, $id);

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Return a success JSON response.
     *
     * @param  mixed  $data
     */
    protected function successResponse($data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $message,
            'code' => $code,
        ], $code);
    }

    /**
     * Return a validation error JSON response.
     */
    protected function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => 'Validation failed',
            'errors' => $exception->errors(),
            'code' => 422,
        ], 422);
    }
}
