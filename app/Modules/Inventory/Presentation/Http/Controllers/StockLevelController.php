<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\CreateStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\DeleteStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\GetStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\ListStockLevelsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockLevels\UpdateStockLevelServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListStockLevelRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertStockLevelRequest;
use Modules\Inventory\Presentation\Http\Resources\StockLevelResource;

final class StockLevelController extends Controller
{
    public function __construct(
        private readonly ListStockLevelsServiceInterface $listService,
        private readonly GetStockLevelServiceInterface $getService,
        private readonly CreateStockLevelServiceInterface $createService,
        private readonly UpdateStockLevelServiceInterface $updateService,
        private readonly DeleteStockLevelServiceInterface $deleteService,
    ) {
    }

    public function index(ListStockLevelRequest $request): JsonResponse
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
            'data' => StockLevelResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|StockLevelResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new StockLevelResource($result->valueOrFail());
    }

    public function store(UpsertStockLevelRequest $request): JsonResponse|StockLevelResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new StockLevelResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertStockLevelRequest $request, int|string $id): JsonResponse|StockLevelResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new StockLevelResource($result->valueOrFail());
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