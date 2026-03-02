<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Product\Application\DTOs\AddUomConversionDTO;
use Modules\Product\Application\DTOs\CreateUomDTO;
use Modules\Product\Application\Services\UomService;

/**
 * UOM controller.
 *
 * Input validation, authorization checks, and response formatting ONLY.
 * No business logic — all delegated to UomService.
 *
 * @OA\Tag(name="UOM", description="Unit of Measure management endpoints")
 */
class UomController extends Controller
{
    public function __construct(private readonly UomService $service) {}

    /**
     * @OA\Get(
     *     path="/api/v1/uoms",
     *     tags={"UOM"},
     *     summary="List all units of measure",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of UOMs"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(): JsonResponse
    {
        $uoms = $this->service->listUoms();

        return ApiResponse::success($uoms, 'Units of measure retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/uoms",
     *     tags={"UOM"},
     *     summary="Create a new unit of measure",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","symbol"},
     *             @OA\Property(property="name", type="string", example="Kilogram"),
     *             @OA\Property(property="symbol", type="string", example="kg"),
     *             @OA\Property(property="is_active", type="boolean", default=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="UOM created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'symbol'    => ['required', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $dto = CreateUomDTO::fromArray($validated);
        $uom = $this->service->createUom($dto);

        return ApiResponse::created($uom, 'Unit of measure created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/uoms/{id}",
     *     tags={"UOM"},
     *     summary="Get a single unit of measure",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="UOM data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $uom = $this->service->showUom($id);

        return ApiResponse::success($uom, 'Unit of measure retrieved.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/uoms/{id}",
     *     tags={"UOM"},
     *     summary="Update a unit of measure",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="symbol", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="UOM updated"),
     *     @OA\Response(response=404, description="Not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:100'],
            'symbol'    => ['sometimes', 'required', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $uom = $this->service->updateUom($id, $validated);

        return ApiResponse::success($uom, 'Unit of measure updated.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/uoms/{id}",
     *     tags={"UOM"},
     *     summary="Delete a unit of measure",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->deleteUom($id);

        return ApiResponse::noContent();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{productId}/uom-conversions",
     *     tags={"UOM"},
     *     summary="List UOM conversions for a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of UOM conversion factors"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function listConversions(int $productId): JsonResponse
    {
        $conversions = $this->service->listConversions($productId);

        return ApiResponse::success($conversions, 'UOM conversions retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/uom-conversions/{id}",
     *     tags={"UOM"},
     *     summary="Get a single UOM conversion",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="UOM conversion data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showConversion(int $id): JsonResponse
    {
        $conversion = $this->service->showConversion($id);

        return ApiResponse::success($conversion, 'UOM conversion retrieved.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/uom-conversions/{id}",
     *     tags={"UOM"},
     *     summary="Delete a UOM conversion",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroyConversion(int $id): JsonResponse
    {
        $this->service->deleteConversion($id);

        return ApiResponse::noContent();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products/{productId}/uom-conversions",
     *     tags={"UOM"},
     *     summary="Add a UOM conversion factor to a product",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"from_uom_id","to_uom_id","factor"},
     *             @OA\Property(property="from_uom_id", type="integer"),
     *             @OA\Property(property="to_uom_id", type="integer"),
     *             @OA\Property(property="factor", type="string", example="12.00000000",
     *                 description="BCMath-safe decimal string — no floating-point values")
     *         )
     *     ),
     *     @OA\Response(response=201, description="UOM conversion added"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function storeConversion(Request $request, int $productId): JsonResponse
    {
        $validated = $request->validate([
            'from_uom_id' => ['required', 'integer', 'min:1'],
            'to_uom_id'   => ['required', 'integer', 'min:1'],
            'factor'      => ['required', 'numeric'],
        ]);

        $validated['product_id'] = $productId;

        $dto = AddUomConversionDTO::fromArray($validated);
        $conversion = $this->service->addConversion($dto);

        return ApiResponse::created($conversion, 'UOM conversion added.');
    }
}
