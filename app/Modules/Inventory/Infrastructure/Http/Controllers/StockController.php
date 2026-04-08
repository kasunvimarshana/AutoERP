<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\StockServiceInterface;
use Modules\Inventory\Application\DTOs\StockAdjustmentData;
use Modules\Inventory\Application\DTOs\StockTransferData;
use Modules\Inventory\Infrastructure\Http\Requests\StockAdjustmentRequest;
use Modules\Inventory\Infrastructure\Http\Requests\StockTransferRequest;
use Modules\Inventory\Infrastructure\Http\Resources\StockItemResource;
use Modules\Inventory\Infrastructure\Http\Resources\StockMovementResource;

/**
 * @OA\Tag(name="Inventory - Stock", description="Stock management: adjustments, transfers, movements")
 */
final class StockController extends AuthorizedController
{
    public function __construct(private readonly StockServiceInterface $service) {}

    /**
     * @OA\Get(
     *     path="/api/inventory/stock",
     *     tags={"Inventory - Stock"},
     *     summary="List stock items for a product or location",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="product_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="location_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Stock items")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $tenantId  = (int) $request->header('X-Tenant-ID');
        $productId = $request->query('product_id');
        $locationId = $request->query('location_id');

        if ($productId !== null) {
            $items = $this->service->getStockByProduct((int) $productId, $tenantId);
        } elseif ($locationId !== null) {
            $items = $this->service->getStockByLocation((int) $locationId);
        } else {
            return StockItemResource::collection(collect());
        }

        return StockItemResource::collection($items);
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/stock/adjust",
     *     tags={"Inventory - Stock"},
     *     summary="Apply a stock adjustment (receipt, issue, scrap, etc.)",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StockAdjustmentRequest")),
     *     @OA\Response(response=201, description="Movement created"),
     *     @OA\Response(response=422, description="Validation or insufficient stock")
     * )
     */
    public function adjust(StockAdjustmentRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = StockAdjustmentData::fromArray($request->validated());
        $movement = $this->service->adjust($dto, $tenantId);

        return (new StockMovementResource($movement))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Post(
     *     path="/api/inventory/stock/transfer",
     *     tags={"Inventory - Stock"},
     *     summary="Transfer stock between two locations",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/StockTransferRequest")),
     *     @OA\Response(response=201, description="Transfer movement created"),
     *     @OA\Response(response=422, description="Validation or insufficient stock")
     * )
     */
    public function transfer(StockTransferRequest $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $dto      = StockTransferData::fromArray($request->validated());
        $movement = $this->service->transfer($dto, $tenantId);

        return (new StockMovementResource($movement))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/inventory/stock/movements",
     *     tags={"Inventory - Stock"},
     *     summary="List stock movements with optional filters",
     *     security={{"passport":{}}},
     *     @OA\Parameter(name="X-Tenant-ID", in="header", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="product_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="location_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="movement_type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="reference", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated movements")
     * )
     */
    public function movements(Request $request): ResourceCollection
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $perPage  = (int) $request->query('per_page', 15);
        $filters  = array_filter(
            $request->only(['product_id', 'location_id', 'movement_type', 'reference']),
            static fn ($v) => $v !== null && $v !== ''
        );
        $filters['tenant_id'] = $tenantId;

        return StockMovementResource::collection(
            $this->service->listMovements($filters, $perPage)
        );
    }
}
