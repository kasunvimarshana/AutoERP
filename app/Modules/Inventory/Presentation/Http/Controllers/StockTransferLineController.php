<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\CreateStockTransferLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\DeleteStockTransferLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\GetStockTransferLineServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\ListStockTransferLinesServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransferLines\UpdateStockTransferLineServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListStockTransferLineRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertStockTransferLineRequest;
use Modules\Inventory\Presentation\Http\Resources\StockTransferLineResource;

final class StockTransferLineController extends Controller
{
    public function __construct(
        private readonly ListStockTransferLinesServiceInterface $listService,
        private readonly GetStockTransferLineServiceInterface $getService,
        private readonly CreateStockTransferLineServiceInterface $createService,
        private readonly UpdateStockTransferLineServiceInterface $updateService,
        private readonly DeleteStockTransferLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListStockTransferLineRequest $request): JsonResponse
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
            'data' => StockTransferLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|StockTransferLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new StockTransferLineResource($result->valueOrFail());
    }

    public function store(UpsertStockTransferLineRequest $request): JsonResponse|StockTransferLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new StockTransferLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertStockTransferLineRequest $request, int|string $id): JsonResponse|StockTransferLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new StockTransferLineResource($result->valueOrFail());
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