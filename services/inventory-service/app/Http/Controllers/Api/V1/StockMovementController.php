<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Contracts\StockMovementRepositoryInterface;
use App\Http\Requests\StockMovement\CreateStockMovementRequest;
use App\Http\Resources\StockMovement\StockMovementResource;
use App\Domain\Contracts\StockServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockMovementRepositoryInterface $movementRepository,
        private readonly StockServiceInterface $stockService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId  = $request->attributes->get('tenant_id');
        $movements = $this->movementRepository->getByTenant($tenantId, $request->all());
        return response()->json([
            'data' => StockMovementResource::collection($movements),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $movement = $this->movementRepository->findByIdAndTenant($id, $tenantId);
        return (new StockMovementResource($movement))->response();
    }

    public function store(CreateStockMovementRequest $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');
        $userId   = $request->attributes->get('auth_user_id');
        $data     = $request->validated();

        $result = $this->stockService->adjustStock(
            tenantId:      $tenantId,
            productId:     $data['product_id'],
            warehouseId:   $data['warehouse_id'],
            quantity:      (float) $data['quantity'],
            type:          $data['type'],
            referenceId:   $data['reference_id'] ?? null,
            referenceType: $data['reference_type'] ?? null,
            notes:         $data['notes'] ?? null,
            performedBy:   $userId,
        );

        return (new StockMovementResource($result['movement']))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
