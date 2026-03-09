<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Contracts\StockServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockReservation\CreateStockReservationRequest;
use App\Http\Resources\Stock\StockLevelResource;
use App\Http\Resources\StockMovement\StockMovementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StockController extends Controller
{
    public function __construct(private readonly StockServiceInterface $stockService)
    {
    }

    public function getStockLevel(Request $request, string $productId, string $warehouseId): JsonResponse
    {
        $tenantId   = $request->attributes->get('tenant_id');
        $stockLevel = $this->stockService->getStockLevel($tenantId, $productId, $warehouseId);
        return (new StockLevelResource($stockLevel))->response();
    }

    public function adjustStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'     => ['required', 'uuid'],
            'warehouse_id'   => ['required', 'uuid'],
            'quantity'       => ['required', 'numeric', 'not_in:0'],
            'type'           => ['required', 'string'],
            'reference_id'   => ['nullable', 'string'],
            'reference_type' => ['nullable', 'string'],
            'notes'          => ['nullable', 'string'],
        ]);

        $tenantId = $request->attributes->get('tenant_id');
        $userId   = $request->attributes->get('auth_user_id');

        $result = $this->stockService->adjustStock(
            tenantId:      $tenantId,
            productId:     $request->input('product_id'),
            warehouseId:   $request->input('warehouse_id'),
            quantity:      (float) $request->input('quantity'),
            type:          $request->input('type'),
            referenceId:   $request->input('reference_id'),
            referenceType: $request->input('reference_type'),
            notes:         $request->input('notes'),
            performedBy:   $userId,
        );

        return response()->json([
            'message'     => 'Stock adjusted successfully.',
            'stock_level' => new StockLevelResource($result['stock_level']),
            'movement'    => new StockMovementResource($result['movement']),
        ]);
    }

    public function transferStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'       => ['required', 'uuid'],
            'from_warehouse_id'=> ['required', 'uuid'],
            'to_warehouse_id'  => ['required', 'uuid', 'different:from_warehouse_id'],
            'quantity'         => ['required', 'numeric', 'min:0.001'],
            'notes'            => ['nullable', 'string'],
        ]);

        $tenantId = $request->attributes->get('tenant_id');
        $userId   = $request->attributes->get('auth_user_id');

        $result = $this->stockService->transferStock(
            tenantId:         $tenantId,
            productId:        $request->input('product_id'),
            fromWarehouseId:  $request->input('from_warehouse_id'),
            toWarehouseId:    $request->input('to_warehouse_id'),
            quantity:         (float) $request->input('quantity'),
            notes:            $request->input('notes'),
            performedBy:      $userId,
        );

        return response()->json([
            'message'      => 'Stock transferred successfully.',
            'from_level'   => new StockLevelResource($result['from_level']),
            'to_level'     => new StockLevelResource($result['to_level']),
        ]);
    }

    public function reserveStock(CreateStockReservationRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $data     = $request->validated();

        $reservation = $this->stockService->reserveStock(
            tenantId:      $tenantId,
            productId:     $data['product_id'],
            warehouseId:   $data['warehouse_id'],
            quantity:      (int) $data['quantity'],
            referenceId:   $data['reference_id'],
            referenceType: $data['reference_type'],
            expiresAt:     isset($data['expires_at']) ? new \DateTime($data['expires_at']) : null,
            notes:         $data['notes'] ?? null,
        );

        return response()->json([
            'message'     => 'Stock reserved successfully.',
            'reservation' => $reservation,
        ], Response::HTTP_CREATED);
    }

    public function commitReservation(Request $request, string $reservationId): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $userId   = $request->attributes->get('auth_user_id');
        $result   = $this->stockService->commitReservation($tenantId, $reservationId, $userId);

        return response()->json([
            'message'     => 'Reservation committed successfully.',
            'stock_level' => new StockLevelResource($result['stock_level']),
            'movement'    => new StockMovementResource($result['movement']),
        ]);
    }

    public function releaseReservation(Request $request, string $reservationId): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $userId   = $request->attributes->get('auth_user_id');
        $result   = $this->stockService->releaseReservation($tenantId, $reservationId, $userId);

        return response()->json([
            'message'     => 'Reservation released successfully.',
            'stock_level' => new StockLevelResource($result['stock_level']),
        ]);
    }
}
