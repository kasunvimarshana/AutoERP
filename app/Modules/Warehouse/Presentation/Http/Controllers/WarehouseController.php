<?php

declare(strict_types=1);

namespace Modules\Warehouse\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\GetWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\ListWarehousesServiceInterface;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Presentation\Http\Requests\ListWarehouseRequest;
use Modules\Warehouse\Presentation\Http\Requests\UpsertWarehouseRequest;
use Modules\Warehouse\Presentation\Http\Resources\WarehouseResource;

final class WarehouseController extends Controller
{
    public function __construct(
        private readonly ListWarehousesServiceInterface $listService,
        private readonly GetWarehouseServiceInterface $getService,
        private readonly CreateWarehouseServiceInterface $createService,
        private readonly UpdateWarehouseServiceInterface $updateService,
        private readonly DeleteWarehouseServiceInterface $deleteService,
    ) {
    }

    public function index(ListWarehouseRequest $request): JsonResponse
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
            'data' => WarehouseResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|WarehouseResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new WarehouseResource($result->valueOrFail());
    }

    public function store(UpsertWarehouseRequest $request): JsonResponse|WarehouseResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new WarehouseResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertWarehouseRequest $request, int|string $id): JsonResponse|WarehouseResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'WAREHOUSE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new WarehouseResource($result->valueOrFail());
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