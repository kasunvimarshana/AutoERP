<?php

namespace Services\Product\Http\Controllers;

use Services\Product\Domain\ProductService;
use Services\Product\Http\Requests\CreateProductRequest;
use Services\Product\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;

/**
 * API-First Design: Thin Controller
 * Pipeline: Controller -> Service -> Repository
 */
class ProductController
{
    private ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/v1/products
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        // 1. Validation (via Request Class)
        // 2. Business Logic (via Service)
        $product = $this->service->createProduct($request->validated());

        // 3. API Response (via Resource Class)
        return response()->json(new ProductResource($product), 201);
    }

    /**
     * GET /api/v1/products/{id}
     */
    public function show(string $id): JsonResponse
    {
        $product = $this->service->getProduct($id);
        
        return response()->json(new ProductResource($product));
    }
}
