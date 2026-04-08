<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Returns\Application\Contracts\CreateStockReturnLineServiceInterface;
use Modules\Returns\Application\Contracts\DeleteStockReturnLineServiceInterface;
use Modules\Returns\Application\Contracts\FailQualityCheckServiceInterface;
use Modules\Returns\Application\Contracts\FindStockReturnLineServiceInterface;
use Modules\Returns\Application\Contracts\PassQualityCheckServiceInterface;
use Modules\Returns\Application\Contracts\UpdateStockReturnLineServiceInterface;
use Modules\Returns\Application\DTOs\StockReturnLineData;
use Modules\Returns\Application\DTOs\UpdateStockReturnLineData;
use Modules\Returns\Infrastructure\Http\Requests\StoreStockReturnLineRequest;
use Modules\Returns\Infrastructure\Http\Requests\UpdateStockReturnLineRequest;
use Modules\Returns\Infrastructure\Http\Resources\StockReturnLineCollection;
use Modules\Returns\Infrastructure\Http\Resources\StockReturnLineResource;

class StockReturnLineController extends AuthorizedController
{
    public function __construct(
        protected FindStockReturnLineServiceInterface $findService,
        protected CreateStockReturnLineServiceInterface $createService,
        protected UpdateStockReturnLineServiceInterface $updateService,
        protected DeleteStockReturnLineServiceInterface $deleteService,
        protected PassQualityCheckServiceInterface $passQualityCheckService,
        protected FailQualityCheckServiceInterface $failQualityCheckService,
    ) {}

    public function index(Request $request): StockReturnLineCollection
    {
        $filters = $request->only(['tenant_id', 'stock_return_id']);
        return new StockReturnLineCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreStockReturnLineRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = StockReturnLineData::fromArray([
            'tenantId'          => $v['tenant_id'],
            'stockReturnId'     => $v['stock_return_id'],
            'productId'         => $v['product_id'],
            'quantityRequested' => $v['quantity_requested'],
            'variationId'       => $v['variation_id'] ?? null,
            'batchId'           => $v['batch_id'] ?? null,
            'serialNumberId'    => $v['serial_number_id'] ?? null,
            'uomId'             => $v['uom_id'] ?? null,
            'unitPrice'         => $v['unit_price'] ?? null,
            'unitCost'          => $v['unit_cost'] ?? null,
            'condition'         => $v['condition'] ?? 'good',
            'disposition'       => $v['disposition'] ?? 'restock',
            'notes'             => $v['notes'] ?? null,
        ]);

        $line = $this->createService->execute($dto->toArray());
        return (new StockReturnLineResource($line))->response()->setStatusCode(201);
    }

    public function show(int $id): StockReturnLineResource|JsonResponse
    {
        $line = $this->findService->find($id);
        if (! $line) { return response()->json(['message' => 'Not found'], 404); }
        return new StockReturnLineResource($line);
    }

    public function update(UpdateStockReturnLineRequest $request, int $id): StockReturnLineResource
    {
        $v   = $request->validated();
        $dto = UpdateStockReturnLineData::fromArray(array_merge(['id' => $id], [
            'quantityApproved' => $v['quantity_approved'] ?? null,
            'condition'        => $v['condition'] ?? null,
            'disposition'      => $v['disposition'] ?? null,
            'notes'            => $v['notes'] ?? null,
        ]));
        return new StockReturnLineResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);
        return response()->json(['message' => 'Stock return line deleted successfully']);
    }

    public function passQualityCheck(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['checked_by' => 'required|integer|min:1']);
        $line = $this->passQualityCheckService->execute([
            'id'         => $id,
            'checked_by' => $validated['checked_by'],
        ]);
        return (new StockReturnLineResource($line))->response();
    }

    public function failQualityCheck(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['checked_by' => 'required|integer|min:1']);
        $line = $this->failQualityCheckService->execute([
            'id'         => $id,
            'checked_by' => $validated['checked_by'],
        ]);
        return (new StockReturnLineResource($line))->response();
    }
}
