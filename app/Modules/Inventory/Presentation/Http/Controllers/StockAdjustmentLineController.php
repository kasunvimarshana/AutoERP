<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\CreateStockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\DeleteStockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\GetStockAdjustmentLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\ListStockAdjustmentLinesServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockAdjustmentLines\UpdateStockAdjustmentLineServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListStockAdjustmentLineRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertStockAdjustmentLineRequest;
use Modules\Inventory\Presentation\Http\Resources\StockAdjustmentLineResource;

final class StockAdjustmentLineController extends Controller
{
    public function __construct(
        private readonly ListStockAdjustmentLinesServiceInterface $listService,
        private readonly GetStockAdjustmentLineServiceInterface $getService,
        private readonly CreateStockAdjustmentLineServiceInterface $createService,
        private readonly UpdateStockAdjustmentLineServiceInterface $updateService,
        private readonly DeleteStockAdjustmentLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListStockAdjustmentLineRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => StockAdjustmentLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|StockAdjustmentLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new StockAdjustmentLineResource($result->valueOrFail());
    }

    public function store(UpsertStockAdjustmentLineRequest $request): JsonResponse|StockAdjustmentLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new StockAdjustmentLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertStockAdjustmentLineRequest $request, int|string $id): JsonResponse|StockAdjustmentLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new StockAdjustmentLineResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}