<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\CreateInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventoryValuationLayerServiceInterface;
use Modules\Inventory\Application\DTOs\InventoryValuationLayerData;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventoryValuationLayerRequest;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryValuationLayerCollection;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryValuationLayerResource;

class InventoryValuationLayerController extends AuthorizedController
{
    public function __construct(
        protected FindInventoryValuationLayerServiceInterface $findService,
        protected CreateInventoryValuationLayerServiceInterface $createService,
    ) {}

    public function index(Request $request): InventoryValuationLayerCollection
    {
        $filters = $request->only(['product_id', 'valuation_method', 'is_closed', 'tenant_id']);
        return new InventoryValuationLayerCollection($this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1)));
    }

    public function store(StoreInventoryValuationLayerRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = InventoryValuationLayerData::fromArray([
            'tenantId'        => $v['tenant_id'],
            'productId'       => $v['product_id'],
            'variationId'     => $v['variation_id'] ?? null,
            'batchId'         => $v['batch_id'] ?? null,
            'locationId'      => $v['location_id'] ?? null,
            'layerDate'       => $v['layer_date'],
            'qtyIn'           => $v['qty_in'],
            'unitCost'        => $v['unit_cost'],
            'currency'        => $v['currency'] ?? 'USD',
            'valuationMethod' => $v['valuation_method'] ?? 'fifo',
            'referenceType'   => $v['reference_type'] ?? null,
            'referenceId'     => $v['reference_id'] ?? null,
            'metadata'        => $v['metadata'] ?? null,
        ]);
        $layer = $this->createService->execute($dto->toArray());
        return (new InventoryValuationLayerResource($layer))->response()->setStatusCode(201);
    }

    public function show(int $id): InventoryValuationLayerResource|JsonResponse
    {
        $layer = $this->findService->find($id);
        if (! $layer) { return response()->json(['message' => 'Not found'], 404); }
        return new InventoryValuationLayerResource($layer);
    }
}
