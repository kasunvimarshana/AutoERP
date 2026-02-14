<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Product API Controller
 * 
 * Handles HTTP requests for Product resource.
 * Demonstrates the complete implementation of the CRUD framework.
 * 
 * @OA\Tag(
 *     name="Products",
 *     description="API Endpoints for managing products"
 * )
 */
class ProductController extends BaseApiController
{
    /**
     * Resource name for responses
     */
    protected string $resourceName = 'product';

    /**
     * Constructor
     *
     * @param ProductService $service
     */
    public function __construct(ProductService $service)
    {
        parent::__construct($service);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     summary="List all products",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="fields",
     *         in="query",
     *         description="Comma-separated list of fields to return",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="with",
     *         in="query",
     *         description="Comma-separated list of relations to eager load",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter[status]",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort fields (prefix with - for descending)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products",
     *     summary="Create a new product",
     *     tags={"Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","status"},
     *             @OA\Property(property="name", type="string", example="Product Name"),
     *             @OA\Property(property="sku", type="string", example="PRD-123"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="price", type="number", example=99.99),
     *             @OA\Property(property="status", type="string", example="active"),
     *             @OA\Property(property="category_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Product created successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        return parent::store($request);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}",
     *     summary="Get a specific product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="with",
     *         in="query",
     *         description="Relations to eager load",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function show(int|string $id, Request $request): JsonResponse
    {
        return parent::show($id, $request);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/products/{id}",
     *     summary="Update a product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="price", type="number"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="category_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Product updated successfully"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function update(int|string $id, Request $request): JsonResponse
    {
        return parent::update($id, $request);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/products/{id}",
     *     summary="Delete a product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Product deleted successfully"),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=500, description="Internal Server Error")
     * )
     */
    public function destroy(int|string $id): JsonResponse
    {
        return parent::destroy($id);
    }

    /**
     * Get active products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $config = $this->buildQueryConfig($request);
            $data = $this->service->getActiveProducts($config);
            
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get low stock products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $threshold = (int) $request->input('threshold', 10);
            $data = $this->service->getLowStock($threshold);
            
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update product stock
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStock(int $id, Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'location_id' => 'required|integer|min:1',
                'quantity' => 'required|integer',
                'batch_number' => 'nullable|string|max:50',
                'expiry_date' => 'nullable|date',
            ]);

            $product = $this->service->updateStock(
                $id,
                $validatedData['location_id'],
                $validatedData['quantity'],
                [
                    'batch_number' => $validatedData['batch_number'] ?? null,
                    'expiry_date' => $validatedData['expiry_date'] ?? null,
                ]
            );

            return $this->successResponse($product, 'Stock updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate store request
     *
     * @param Request $request
     * @return array
     */
    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,discontinued',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);
    }

    /**
     * Validate update request
     *
     * @param Request $request
     * @param int|string $id
     * @return array
     */
    protected function validateUpdate(Request $request, int|string $id): array
    {
        return $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sku' => "nullable|string|max:50|unique:products,sku,{$id}",
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:active,inactive,discontinued',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);
    }

    /**
     * Get searchable fields
     *
     * @return array
     */
    protected function getSearchableFields(): array
    {
        return ['name', 'sku', 'description'];
    }
}
