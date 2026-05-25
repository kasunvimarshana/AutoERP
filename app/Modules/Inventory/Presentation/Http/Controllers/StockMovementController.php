<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\CreateStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\DeleteStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\GetStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\ListStockMovementsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockMovements\UpdateStockMovementServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListStockMovementRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertStockMovementRequest;
use Modules\Inventory\Presentation\Http\Resources\StockMovementResource;

final class StockMovementController extends Controller
{
    public function __construct(
        private readonly ListStockMovementsServiceInterface $listService,
        private readonly GetStockMovementServiceInterface $getService,
        private readonly CreateStockMovementServiceInterface $createService,
        private readonly UpdateStockMovementServiceInterface $updateService,
        private readonly DeleteStockMovementServiceInterface $deleteService,
    ) {
    }

    public function index(ListStockMovementRequest $request): JsonResponse
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
            'data' => StockMovementResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|StockMovementResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new StockMovementResource($result->valueOrFail());
    }

    public function store(UpsertStockMovementRequest $request): JsonResponse|StockMovementResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new StockMovementResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertStockMovementRequest $request, int|string $id): JsonResponse|StockMovementResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new StockMovementResource($result->valueOrFail());
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