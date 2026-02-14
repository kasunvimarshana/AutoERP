<?php

namespace App\Core;

use App\DTOs\QueryConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Generic CRUD Controller
 * 
 * Provides fully dynamic CRUD operations using QueryConfig
 * Extend this class and set $service and $resourceClass properties
 * 
 * Example usage:
 * ```
 * class ProductController extends GenericCrudController
 * {
 *     protected BaseService $service;
 *     protected string $resourceClass = ProductResource::class;
 * 
 *     public function __construct(ProductService $service)
 *     {
 *         $this->service = $service;
 *     }
 * }
 * ```
 * 
 * @package App\Core
 */
abstract class GenericCrudController extends BaseController
{
    /**
     * The service instance
     *
     * @var BaseService
     */
    protected BaseService $service;

    /**
     * The resource class to use for responses
     *
     * @var string
     */
    protected string $resourceClass;

    /**
     * Display a listing of resources with dynamic query support
     * 
     * Supports query parameters:
     * - select: Comma-separated field list or array
     * - search: Global search term
     * - search_fields: Comma-separated searchable fields
     * - filters[field]: Field-level filters
     * - sort: field:direction or field (default asc)
     * - per_page: Results per page
     * - page: Page number
     * - with: Comma-separated relations to eager load
     * - with_count: Comma-separated relations to count
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $config = $this->buildQueryConfig($request);
            $results = $this->service->query($config);

            // Apply resource transformation if collection
            if ($this->resourceClass && class_exists($this->resourceClass)) {
                if (method_exists($results, 'items')) {
                    // Paginated results
                    $resourceClass = $this->resourceClass;
                    $results->setCollection(
                        $results->getCollection()->map(fn($item) => new $resourceClass($item))
                    );
                    return $this->success($results);
                } else {
                    // Non-paginated collection
                    $resourceClass = $this->resourceClass;
                    return $this->collection($resourceClass::collection($results));
                }
            }

            return $this->success($results);
        } catch (\InvalidArgumentException $e) {
            return $this->validationError([], $e->getMessage());
        } catch (\Exception $e) {
            return $this->error('Failed to fetch resources: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created resource
     * 
     * Note: Child classes should override this method with proper FormRequest validation
     * Example:
     * ```
     * public function store(CreateProductRequest $request): JsonResponse
     * {
     *     return parent::store($request);
     * }
     * ```
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Use all() instead of validated() since base Request doesn't have validation rules
            // Child controllers should override with FormRequest
            $data = method_exists($request, 'validated') ? $request->validated() : $request->all();
            $resource = $this->service->create($data);

            if ($this->resourceClass && class_exists($this->resourceClass)) {
                $resourceClass = $this->resourceClass;
                return $this->created(new $resourceClass($resource));
            }

            return $this->created($resource);
        } catch (\Exception $e) {
            return $this->error('Failed to create resource: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            // Support eager loading via 'with' parameter
            $with = $request->input('with', []);
            if (is_string($with)) {
                $with = explode(',', $with);
            }

            $resource = $this->service->findById($id, $with);

            if (!$resource) {
                return $this->notFound();
            }

            if ($this->resourceClass && class_exists($this->resourceClass)) {
                $resourceClass = $this->resourceClass;
                return $this->resource(new $resourceClass($resource));
            }

            return $this->success($resource);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch resource: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified resource
     * 
     * Note: Child classes should override this method with proper FormRequest validation
     * Example:
     * ```
     * public function update(UpdateProductRequest $request, int $id): JsonResponse
     * {
     *     return parent::update($request, $id);
     * }
     * ```
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Use all() instead of validated() since base Request doesn't have validation rules
            // Child controllers should override with FormRequest
            $data = method_exists($request, 'validated') ? $request->validated() : $request->all();
            $result = $this->service->update($id, $data);

            if (!$result) {
                return $this->notFound();
            }

            return $this->success(null, 'Resource updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update resource: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->service->delete($id);

            if (!$result) {
                return $this->notFound();
            }

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->error('Failed to delete resource: ' . $e->getMessage(), 500);
        }
    }
}
