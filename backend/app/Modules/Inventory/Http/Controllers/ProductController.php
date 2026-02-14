<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Inventory\Services\ProductService;
use App\Modules\Inventory\Requests\CreateProductRequest;
use App\Modules\Inventory\Requests\UpdateProductRequest;
use App\Modules\Inventory\Resources\ProductResource;
use Illuminate\Http\JsonResponse;

/**
 * Product Controller
 * 
 * Handles CRUD operations for product management
 * 
 * @package App\Modules\Inventory\Http\Controllers
 */
class ProductController extends BaseController
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of products
     * 
     * Enhanced with dynamic query support:
     * - ?select=id,name,price - Field selection
     * - ?search=laptop&search_fields=name,description - Global search
     * - ?filters[status]=active - Filtering
     * - ?sort=price:desc - Sorting
     * - ?per_page=20&page=1 - Pagination
     * - ?with=skus,tenant - Eager loading
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            // Build query configuration from request
            $config = $this->buildQueryConfig($request);
            
            // Execute dynamic query
            $products = $this->service->query($config);
            
            // Return paginated or collection response
            if (method_exists($products, 'items')) {
                // Paginated results
                return $this->success($products);
            } else {
                // Non-paginated collection
                return $this->collection(ProductResource::collection($products));
            }
        } catch (\InvalidArgumentException $e) {
            return $this->validationError([], $e->getMessage());
        } catch (\Exception $e) {
            return $this->error('Failed to fetch products: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created product
     *
     * @param CreateProductRequest $request
     * @return JsonResponse
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $product = $this->service->createProduct($request->validated());
        return $this->created(new ProductResource($product));
    }

    /**
     * Display the specified product
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->service->getProduct($id);
        
        if (!$product) {
            return $this->notFound();
        }
        
        return $this->resource(new ProductResource($product));
    }

    /**
     * Update the specified product
     *
     * @param UpdateProductRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateProduct($id, $request->validated());
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'Product updated successfully');
    }

    /**
     * Remove the specified product
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteProduct($id);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->noContent();
    }
}
