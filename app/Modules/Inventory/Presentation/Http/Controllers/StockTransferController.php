<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\CreateStockTransferServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\DeleteStockTransferServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\GetStockTransferServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\ListStockTransfersServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockTransfers\UpdateStockTransferServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListStockTransferRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertStockTransferRequest;
use Modules\Inventory\Presentation\Http\Resources\StockTransferResource;

final class StockTransferController extends Controller
{
    public function __construct(
        private readonly ListStockTransfersServiceInterface $listService,
        private readonly GetStockTransferServiceInterface $getService,
        private readonly CreateStockTransferServiceInterface $createService,
        private readonly UpdateStockTransferServiceInterface $updateService,
        private readonly DeleteStockTransferServiceInterface $deleteService,
    ) {
    }

    public function index(ListStockTransferRequest $request): JsonResponse
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
            'data' => StockTransferResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|StockTransferResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new StockTransferResource($result->valueOrFail());
    }

    public function store(UpsertStockTransferRequest $request): JsonResponse|StockTransferResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new StockTransferResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertStockTransferRequest $request, int|string $id): JsonResponse|StockTransferResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new StockTransferResource($result->valueOrFail());
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