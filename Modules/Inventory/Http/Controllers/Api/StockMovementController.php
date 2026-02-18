<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Http\Requests\ReceiveStockRequest;
use Modules\Inventory\Http\Requests\ShipStockRequest;
use Modules\Inventory\Http\Requests\TransferStockRequest;
use Modules\Inventory\Http\Resources\StockMovementResource;
use Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Modules\Inventory\Services\StockMovementService;

/**
 * Stock Movement API Controller
 */
class StockMovementController extends Controller
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected StockMovementRepositoryInterface $stockMovementRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $movements = $this->stockMovementRepository->paginate($perPage);

        return StockMovementResource::collection($movements)->response();
    }

    public function show(int $id): JsonResponse
    {
        $movement = $this->stockMovementRepository->findById($id);

        if (! $movement) {
            return response()->json(['message' => 'Movement not found'], 404);
        }

        return (new StockMovementResource($movement))->response();
    }

    public function receive(ReceiveStockRequest $request): JsonResponse
    {
        $movement = $this->stockMovementService->receiveStock($request->validated());

        return (new StockMovementResource($movement))->response()->setStatusCode(201);
    }

    public function ship(ShipStockRequest $request): JsonResponse
    {
        $movement = $this->stockMovementService->shipStock($request->validated());

        return (new StockMovementResource($movement))->response()->setStatusCode(201);
    }

    public function transfer(TransferStockRequest $request): JsonResponse
    {
        [$outbound, $inbound] = $this->stockMovementService->transferStock($request->validated());

        return response()->json([
            'message' => 'Stock transferred successfully',
            'outbound_movement' => new StockMovementResource($outbound),
            'inbound_movement' => new StockMovementResource($inbound),
        ], 201);
    }

    public function history(int $productId): JsonResponse
    {
        $movements = $this->stockMovementService->getMovementHistory($productId, 50);

        return StockMovementResource::collection($movements)->response();
    }
}
