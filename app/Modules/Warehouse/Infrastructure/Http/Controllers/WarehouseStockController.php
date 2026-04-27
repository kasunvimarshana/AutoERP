<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\FindStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\FindStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\Inventory\Infrastructure\Http\Resources\StockMovementResource;
use Modules\Warehouse\Infrastructure\Http\Requests\ListWarehouseStockLevelRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\ListWarehouseStockMovementRequest;
use Modules\Warehouse\Infrastructure\Http\Requests\StoreWarehouseStockMovementRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WarehouseStockController extends AuthorizedController
{
    public function __construct(
        private readonly RecordStockMovementServiceInterface $recordStockMovementService,
        private readonly FindStockMovementServiceInterface $findStockMovementService,
        private readonly FindStockLevelServiceInterface $findStockLevelService,
    ) {}

    public function movements(ListWarehouseStockMovementRequest $request, int $warehouse): JsonResponse
    {
        $this->authorize('viewAny', StockMovement::class);

        $validated = $request->validated();

        $movements = $this->findStockMovementService->listByWarehouse(
            tenantId: (int) $validated['tenant_id'],
            warehouseId: $warehouse,
            filters: array_filter([
                'product_id' => $validated['product_id'] ?? null,
                'movement_type' => $validated['movement_type'] ?? null,
                'from_location_id' => $validated['from_location_id'] ?? null,
                'to_location_id' => $validated['to_location_id'] ?? null,
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return response()->json($movements);
    }

    public function storeMovement(StoreWarehouseStockMovementRequest $request, int $warehouse): JsonResponse
    {
        $this->authorize('create', StockMovement::class);

        $payload = $request->validated();
        $payload['warehouse_id'] = $warehouse;

        $movement = $this->recordStockMovementService->execute($payload);

        return (new StockMovementResource($movement))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function stockLevels(ListWarehouseStockLevelRequest $request, int $warehouse): JsonResponse
    {
        $this->authorize('viewAny', StockMovement::class);
        $validated = $request->validated();

        $levels = $this->findStockLevelService->listByWarehouse(
            tenantId: (int) $validated['tenant_id'],
            warehouseId: $warehouse,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($levels);
    }
}
