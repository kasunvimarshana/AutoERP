<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\BaseController;
use Modules\Inventory\Application\Contracts\InventoryServiceInterface;
use Modules\Inventory\Application\DTOs\StockMovementData;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryItemResource;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryMovementResource;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryItemModel;

class InventoryController extends BaseController
{
    public function __construct(InventoryServiceInterface $service)
    {
        parent::__construct($service, InventoryItemResource::class, StockMovementData::class);
    }

    protected function getModelClass(): string
    {
        return InventoryItemModel::class;
    }

    /**
     * Get stock levels for a specific product.
     */
    public function stockLevels(Request $request, string $productId): JsonResponse
    {
        /** @var \Modules\Inventory\Application\Contracts\InventoryServiceInterface $service */
        $service = $this->service;
        $levels = $service->getStockLevels($productId);

        return response()->json(InventoryItemResource::collection($levels));
    }

    /**
     * Record a stock movement (in/out/transfer).
     */
    public function recordMovement(Request $request): JsonResponse
    {
        /** @var \Modules\Inventory\Application\Contracts\InventoryServiceInterface $service */
        $service = $this->service;
        $movement = $service->recordMovement($request->all());

        return (new InventoryMovementResource($movement))->response()->setStatusCode(201);
    }

    /**
     * Reserve stock for a reference entity (e.g. order line).
     */
    public function reserve(Request $request): JsonResponse
    {
        /** @var \Modules\Inventory\Application\Contracts\InventoryServiceInterface $service */
        $service = $this->service;
        $service->reserveStock(
            $request->input('product_id'),
            $request->input('warehouse_id'),
            (float) $request->input('quantity'),
            $request->input('reference_type'),
            $request->input('reference_id'),
        );

        return response()->json(['message' => 'Stock reserved.']);
    }

    /**
     * Release a previously created stock reservation.
     */
    public function releaseReservation(Request $request, string $reservationId): JsonResponse
    {
        /** @var \Modules\Inventory\Application\Contracts\InventoryServiceInterface $service */
        $service = $this->service;
        $service->releaseReservation($reservationId);

        return response()->json(null, 204);
    }
}
