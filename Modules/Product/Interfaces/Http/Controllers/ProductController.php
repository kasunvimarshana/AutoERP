<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Product\Application\DTOs\CreateProductDTO;
use Modules\Product\Application\Services\ProductService;

/**
 * Product controller.
 *
 * Input validation, authorization checks, and response formatting ONLY.
 * No business logic — all delegated to ProductService.
 *
 * @OA\Tag(name="Product", description="Product catalog management endpoints")
 */
class ProductController extends Controller
{
    public function __construct(private readonly ProductService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     tags={"Product"},
     *     summary="List products (paginated)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Paginated list of products"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $paginator = $this->service->list($perPage);

        return ApiResponse::paginated($paginator, 'Products retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products",
     *     tags={"Product"},
     *     summary="Create a new product",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","sku","type","uom_id"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="type", type="string", enum={"physical","consumable","service","digital","bundle","composite","variant"}),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="uom_id", type="integer"),
     *             @OA\Property(property="buying_uom_id", type="integer", nullable=true),
     *             @OA\Property(property="selling_uom_id", type="integer", nullable=true),
     *             @OA\Property(property="is_active", type="boolean", default=true),
     *             @OA\Property(property="has_serial_tracking", type="boolean", default=false),
     *             @OA\Property(property="has_batch_tracking", type="boolean", default=false),
     *             @OA\Property(property="has_expiry_tracking", type="boolean", default=false),
     *             @OA\Property(property="barcode", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Product created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'sku'                 => ['required', 'string', 'max:100'],
            'type'                => ['required', 'string', 'in:physical,consumable,service,digital,bundle,composite,variant'],
            'description'         => ['nullable', 'string'],
            'uom_id'              => ['required', 'integer', 'min:1'],
            'buying_uom_id'       => ['nullable', 'integer', 'min:1'],
            'selling_uom_id'      => ['nullable', 'integer', 'min:1'],
            'is_active'           => ['nullable', 'boolean'],
            'has_serial_tracking' => ['nullable', 'boolean'],
            'has_batch_tracking'  => ['nullable', 'boolean'],
            'has_expiry_tracking' => ['nullable', 'boolean'],
            'barcode'             => ['nullable', 'string', 'max:255'],
        ]);

        $dto = CreateProductDTO::fromArray($validated);
        $product = $this->service->create($dto);

        return ApiResponse::created($product, 'Product created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}",
     *     tags={"Product"},
     *     summary="Get a single product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Product data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->service->show($id);

        return ApiResponse::success($product, 'Product retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/products/{id}",
     *     tags={"Product"},
     *     summary="Update a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="sku", type="string"),
     *             @OA\Property(property="type", type="string", enum={"physical","consumable","service","digital","bundle","composite","variant"}),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="uom_id", type="integer"),
     *             @OA\Property(property="buying_uom_id", type="integer", nullable=true),
     *             @OA\Property(property="selling_uom_id", type="integer", nullable=true),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="has_serial_tracking", type="boolean"),
     *             @OA\Property(property="has_batch_tracking", type="boolean"),
     *             @OA\Property(property="has_expiry_tracking", type="boolean"),
     *             @OA\Property(property="barcode", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Product updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'sku'                 => ['sometimes', 'required', 'string', 'max:100'],
            'type'                => ['sometimes', 'required', 'string', 'in:physical,consumable,service,digital,bundle,composite,variant'],
            'description'         => ['nullable', 'string'],
            'uom_id'              => ['sometimes', 'required', 'integer', 'min:1'],
            'buying_uom_id'       => ['nullable', 'integer', 'min:1'],
            'selling_uom_id'      => ['nullable', 'integer', 'min:1'],
            'is_active'           => ['nullable', 'boolean'],
            'has_serial_tracking' => ['nullable', 'boolean'],
            'has_batch_tracking'  => ['nullable', 'boolean'],
            'has_expiry_tracking' => ['nullable', 'boolean'],
            'barcode'             => ['nullable', 'string', 'max:255'],
        ]);

        $product = $this->service->update($id, $validated);

        return ApiResponse::success($product, 'Product updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/products/{id}",
     *     tags={"Product"},
     *     summary="Delete a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::noContent();
    }
}
