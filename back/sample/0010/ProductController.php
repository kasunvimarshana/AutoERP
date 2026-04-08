<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Product\Application\ServiceInterfaces\CreateProductServiceInterface;
use Modules\Product\Application\ServiceInterfaces\DeleteProductServiceInterface;
use Modules\Product\Application\ServiceInterfaces\GetProductServiceInterface;
use Modules\Product\Application\ServiceInterfaces\ListProductsServiceInterface;
use Modules\Product\Application\ServiceInterfaces\UpdateProductServiceInterface;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\Exceptions\ProductSkuAlreadyExistsException;
use Modules\Product\Infrastructure\Http\Requests\StoreProductRequest;
use Modules\Product\Infrastructure\Http\Requests\UpdateProductRequest;
use Modules\Product\Infrastructure\Http\Resources\ProductResource;

/**
 * ProductController
 *
 * Thin HTTP adapter — delegates all business logic to Application services.
 * Injected via DI (confirmed KVAutoERP pattern: no direct instantiation).
 */
final class ProductController extends Controller
{
    public function __construct(
        private readonly CreateProductServiceInterface $createService,
        private readonly UpdateProductServiceInterface $updateService,
        private readonly DeleteProductServiceInterface $deleteService,
        private readonly GetProductServiceInterface    $getService,
        private readonly ListProductsServiceInterface  $listService,
    ) {}

    /**
     * @OA\Get(
     *   path="/api/v1/products",
     *   summary="List products",
     *   tags={"Products"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="type",in="query",@OA\Schema(type="string")),
     *   @OA\Parameter(name="status",in="query",@OA\Schema(type="string")),
     *   @OA\Parameter(name="search",in="query",@OA\Schema(type="string")),
     *   @OA\Parameter(name="per_page",in="query",@OA\Schema(type="integer")),
     *   @OA\Response(response=200,description="Paginated product list")
     * )
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $products = $this->listService->execute([
            'tenant_id' => $request->user()->tenant_id,
            'filters'   => $request->only(['type', 'status', 'search', 'category', 'track_batches']),
            'per_page'  => (int) ($request->input('per_page', 25)),
        ]);

        return response()->json(ProductResource::collection($products));
    }

    /**
     * @OA\Post(
     *   path="/api/v1/products",
     *   summary="Create a product",
     *   tags={"Products"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/StoreProductRequest")
     *   ),
     *   @OA\Response(response=201,description="Created")
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = $this->createService->execute(
                array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id])
            );
            return response()->json(new ProductResource($product), 201);
        } catch (ProductSkuAlreadyExistsException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * @OA\Get(
     *   path="/api/v1/products/{id}",
     *   summary="Get a product",
     *   tags={"Products"},
     *   @OA\Parameter(name="id",in="path",required=true,@OA\Schema(type="integer")),
     *   @OA\Response(response=200,description="Product detail")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->getService->execute(['id' => $id]);
            return response()->json(new ProductResource($product));
        } catch (ProductNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Put(
     *   path="/api/v1/products/{id}",
     *   summary="Update a product",
     *   tags={"Products"},
     *   @OA\Parameter(name="id",in="path",required=true,@OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true,@OA\JsonContent(ref="#/components/schemas/UpdateProductRequest")),
     *   @OA\Response(response=200,description="Updated")
     * )
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $product = $this->updateService->execute(
                array_merge($request->validated(), [
                    'id'        => $id,
                    'tenant_id' => $request->user()->tenant_id,
                ])
            );
            return response()->json(new ProductResource($product));
        } catch (ProductNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/products/{id}",
     *   summary="Delete a product",
     *   tags={"Products"},
     *   @OA\Parameter(name="id",in="path",required=true,@OA\Schema(type="integer")),
     *   @OA\Response(response=204,description="Deleted")
     * )
     */
    public function destroy(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        try {
            $this->deleteService->execute([
                'id'        => $id,
                'tenant_id' => $request->user()->tenant_id,
            ]);
            return response()->json(null, 204);
        } catch (ProductNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
