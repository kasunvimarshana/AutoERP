<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\Contracts\StockMovementServiceInterface;
use Modules\Inventory\Infrastructure\Http\Resources\StockMovementResource;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockMovementServiceInterface $stockMovementService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $movements = $this->stockMovementService->getMovementsByProduct(
            $tenantId,
            (string) $request->query('product_id', ''),
        );
        return response()->json(StockMovementResource::collection($movements));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $movement = $this->stockMovementService->recordMovement($tenantId, $request->all());
        return response()->json(new StockMovementResource($movement), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $movement = $this->stockMovementService->getMovement($tenantId, $id);
        return response()->json(new StockMovementResource($movement));
    }
}
