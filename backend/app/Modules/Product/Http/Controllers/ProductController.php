<?php

namespace App\Modules\Product\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Product\Services\ProductService;
use App\Modules\Product\DTOs\ProductDTO;
use App\Modules\Product\DTOs\ProductVariantDTO;
use App\Modules\Product\Http\Requests\StoreProductRequest;
use App\Modules\Product\Http\Requests\UpdateProductRequest;
use App\Modules\Product\Http\Requests\StoreVariantRequest;
use App\Modules\Product\Http\Requests\UpdateVariantRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product management endpoints"
 * )
 */
class ProductController extends BaseController
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="List all products",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $products = $this->service->paginate($perPage, ['tenant', 'productVariants']);
            
            return $this->successResponse($products, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id", "sku", "name", "unit_of_measure", "type"},
     *             @OA\Property(property="tenant_id", type="string", format="uuid"),
     *             @OA\Property(property="sku", type="string", example="PROD-001"),
     *             @OA\Property(property="name", type="string", example="Laptop Computer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="category", type="string", example="Electronics"),
     *             @OA\Property(property="unit_of_measure", type="string", example="pcs"),
     *             @OA\Property(property="is_variant_product", type="boolean", example=false),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="type", type="string", enum={"product", "service"}),
     *             @OA\Property(property="tax_rate", type="number", format="float", example=0.15),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $dto = ProductDTO::fromArray($request->validated());
            $product = $this->service->createProduct($dto);
            
            return $this->successResponse($product, 'Product created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Get a specific product",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Product")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->service->findById($id, ['tenant', 'productVariants']);
            
            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }
            
            return $this->successResponse($product, 'Product retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Update a product",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="category", type="string"),
     *             @OA\Property(property="unit_of_measure", type="string"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="type", type="string", enum={"product", "service"}),
     *             @OA\Property(property="tax_rate", type="number", format="float"),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        try {
            $dto = ProductDTO::fromArray($request->validated());
            $result = $this->service->updateProduct($id, $dto);
            
            if (!$result) {
                return $this->errorResponse('Product not found', 404);
            }
            
            return $this->successResponse(null, 'Product updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/products/{id}",
     *     tags={"Products"},
     *     summary="Delete a product",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Product not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $result = $this->service->deleteProduct($id);
            
            if (!$result) {
                return $this->errorResponse('Product not found', 404);
            }
            
            return $this->successResponse(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}/variants",
     *     tags={"Products"},
     *     summary="Get all variants for a product",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product variants retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ProductVariant"))
     *         )
     *     )
     * )
     */
    public function variants(string $id): JsonResponse
    {
        try {
            $variants = $this->service->getProductVariants($id);
            
            return $this->successResponse($variants, 'Product variants retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products/{id}/variants",
     *     tags={"Products"},
     *     summary="Add a variant to a product",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sku", "variant_name", "cost_price", "selling_price"},
     *             @OA\Property(property="sku", type="string", example="PROD-001-RED-L"),
     *             @OA\Property(property="variant_name", type="string", example="Red - Large"),
     *             @OA\Property(property="attributes", type="object"),
     *             @OA\Property(property="cost_price", type="number", format="float"),
     *             @OA\Property(property="selling_price", type="number", format="float"),
     *             @OA\Property(property="barcode", type="string"),
     *             @OA\Property(property="weight", type="number", format="float"),
     *             @OA\Property(property="dimensions", type="object"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Variant created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Variant created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductVariant")
     *         )
     *     )
     * )
     */
    public function storeVariant(StoreVariantRequest $request, string $id): JsonResponse
    {
        try {
            $dto = ProductVariantDTO::fromArray($request->validated());
            $variant = $this->service->addVariant($id, $dto);
            
            return $this->successResponse($variant, 'Variant created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/products/{id}/variants/{variantId}",
     *     tags={"Products"},
     *     summary="Update a product variant",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="variantId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="variant_name", type="string"),
     *             @OA\Property(property="attributes", type="object"),
     *             @OA\Property(property="cost_price", type="number", format="float"),
     *             @OA\Property(property="selling_price", type="number", format="float"),
     *             @OA\Property(property="barcode", type="string"),
     *             @OA\Property(property="weight", type="number", format="float"),
     *             @OA\Property(property="dimensions", type="object"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Variant updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Variant updated successfully")
     *         )
     *     )
     * )
     */
    public function updateVariant(UpdateVariantRequest $request, string $id, string $variantId): JsonResponse
    {
        try {
            $dto = ProductVariantDTO::fromArray($request->validated());
            $result = $this->service->updateVariant($variantId, $dto);
            
            if (!$result) {
                return $this->errorResponse('Variant not found', 404);
            }
            
            return $this->successResponse(null, 'Variant updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/products/{id}/variants/{variantId}",
     *     tags={"Products"},
     *     summary="Delete a product variant",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="variantId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Variant deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Variant deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroyVariant(string $id, string $variantId): JsonResponse
    {
        try {
            $result = $this->service->deleteVariant($variantId);
            
            if (!$result) {
                return $this->errorResponse('Variant not found', 404);
            }
            
            return $this->successResponse(null, 'Variant deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
