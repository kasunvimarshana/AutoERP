<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\CreateStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\DeleteStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\GetStockReservationServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\ListStockReservationsServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\StockReservations\UpdateStockReservationServiceInterface;
use Modules\Inventory\Presentation\Http\Requests\ListStockReservationRequest;
use Modules\Inventory\Presentation\Http\Requests\UpsertStockReservationRequest;
use Modules\Inventory\Presentation\Http\Resources\StockReservationResource;

final class StockReservationController extends Controller
{
    public function __construct(
        private readonly ListStockReservationsServiceInterface $listService,
        private readonly GetStockReservationServiceInterface $getService,
        private readonly CreateStockReservationServiceInterface $createService,
        private readonly UpdateStockReservationServiceInterface $updateService,
        private readonly DeleteStockReservationServiceInterface $deleteService,
    ) {
    }

    public function index(ListStockReservationRequest $request): JsonResponse
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
            'data' => StockReservationResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|StockReservationResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new StockReservationResource($result->valueOrFail());
    }

    public function store(UpsertStockReservationRequest $request): JsonResponse|StockReservationResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new StockReservationResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertStockReservationRequest $request, int|string $id): JsonResponse|StockReservationResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'INVENTORY_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new StockReservationResource($result->valueOrFail());
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