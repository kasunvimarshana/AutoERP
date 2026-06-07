<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Warehouse\Http\Requests\ListWarehouseRequest;
use Modules\Warehouse\Http\Requests\UpsertWarehouseRequest;
use Modules\Warehouse\Http\Resources\WarehouseResource;
use Modules\Warehouse\Services\Warehouses\CreateWarehouseService;
use Modules\Warehouse\Services\Warehouses\DeleteWarehouseService;
use Modules\Warehouse\Services\Warehouses\GetWarehouseService;
use Modules\Warehouse\Services\Warehouses\ListWarehousesService;
use Modules\Warehouse\Services\Warehouses\UpdateWarehouseService;

final class WarehouseController extends Controller
{
    public function __construct(
        private readonly ListWarehousesService $listService,
        private readonly GetWarehouseService $getService,
        private readonly CreateWarehouseService $createService,
        private readonly UpdateWarehouseService $updateService,
        private readonly DeleteWarehouseService $deleteService,
    ) {}

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
            'meta' => $pageResult->paginationMeta(),
        ]);
    }

    public function show(ListWarehouseRequest $request, int|string $id): JsonResponse|WarehouseResource
    {
        $result = $this->getService->execute($id, $request->tenantId(), $request->organizationUnitId());

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
        $result = $this->updateService->execute(
            $id,
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
        );

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'WAREHOUSE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new WarehouseResource($result->valueOrFail());
    }

    public function destroy(ListWarehouseRequest $request, int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id, $request->tenantId(), $request->organizationUnitId());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
