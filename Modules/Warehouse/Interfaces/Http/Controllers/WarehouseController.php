<?php

declare(strict_types=1);

namespace Modules\Warehouse\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Interfaces\Http\Resources\ApiResponse;
use Modules\Warehouse\Application\DTOs\CreatePickingOrderDTO;
use Modules\Warehouse\Application\Services\WarehouseService;

/**
 * Warehouse controller.
 *
 * Input validation, authorization, and response formatting only.
 * All business logic is delegated to WarehouseService.
 *
 * @OA\Tag(name="Warehouse", description="Warehouse management endpoints")
 */
class WarehouseController extends Controller
{
    public function __construct(private readonly WarehouseService $service) {}

    /**
     * @OA\Post(
     *     path="/api/v1/warehouse/picking-orders",
     *     tags={"Warehouse"},
     *     summary="Create a picking order",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"warehouse_id","picking_type","lines"},
     *             @OA\Property(property="warehouse_id", type="integer"),
     *             @OA\Property(property="picking_type", type="string", enum={"batch","wave","zone"}),
     *             @OA\Property(property="reference_type", type="string", nullable=true),
     *             @OA\Property(property="reference_id", type="integer", nullable=true),
     *             @OA\Property(
     *                 property="lines",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="product_id", type="integer"),
     *                     @OA\Property(property="quantity_requested", type="string", example="10.0000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Picking order created"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function createPickingOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id'               => ['required', 'integer'],
            'picking_type'               => ['required', 'string', 'in:batch,wave,zone'],
            'reference_type'             => ['nullable', 'string', 'max:255'],
            'reference_id'               => ['nullable', 'integer'],
            'lines'                      => ['required', 'array', 'min:1'],
            'lines.*.product_id'         => ['required', 'integer'],
            'lines.*.quantity_requested' => ['required', 'numeric'],
        ]);

        $dto          = CreatePickingOrderDTO::fromArray($validated);
        $pickingOrder = $this->service->createPickingOrder($dto);

        return ApiResponse::created($pickingOrder, 'Picking order created.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/warehouse/picking-orders",
     *     tags={"Warehouse"},
     *     summary="List all picking orders",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="List of picking orders"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listPickingOrders(): JsonResponse
    {
        $orders = $this->service->listPickingOrders();

        return ApiResponse::success($orders, 'Picking orders retrieved.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/warehouse/picking-orders/{id}",
     *     tags={"Warehouse"},
     *     summary="Get a single picking order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Picking order data"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function showPickingOrder(int $id): JsonResponse
    {
        $order = $this->service->showPickingOrder($id);

        return ApiResponse::success($order, 'Picking order retrieved.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/warehouse/picking-orders/{id}/complete",
     *     tags={"Warehouse"},
     *     summary="Complete a picking order",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Picking order completed"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function completePickingOrder(int $id): JsonResponse
    {
        $order = $this->service->completePickingOrder($id);

        return ApiResponse::success($order, 'Picking order completed.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/warehouse/putaway/{productId}/{warehouseId}",
     *     tags={"Warehouse"},
     *     summary="Get putaway recommendation for a product in a warehouse",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="productId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="warehouseId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Putaway recommendation or null"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getPutawayRecommendation(int $productId, int $warehouseId): JsonResponse
    {
        $recommendation = $this->service->getPutawayRecommendation($productId, $warehouseId);

        return ApiResponse::success($recommendation, 'Putaway recommendation retrieved.');
    }
}
