<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Warehouse\Http\Requests\ListWarehouseLocationRequest;
use Modules\Warehouse\Http\Requests\UpsertWarehouseLocationRequest;
use Modules\Warehouse\Http\Resources\WarehouseLocationResource;
use Modules\Warehouse\Services\WarehouseLocations\CreateWarehouseLocationService;
use Modules\Warehouse\Services\WarehouseLocations\DeleteWarehouseLocationService;
use Modules\Warehouse\Services\WarehouseLocations\GetWarehouseLocationService;
use Modules\Warehouse\Services\WarehouseLocations\ListWarehouseLocationsService;
use Modules\Warehouse\Services\WarehouseLocations\UpdateWarehouseLocationService;

final class WarehouseLocationController extends Controller
{
    public function __construct(
        private readonly ListWarehouseLocationsService $listService,
        private readonly GetWarehouseLocationService $getService,
        private readonly CreateWarehouseLocationService $createService,
        private readonly UpdateWarehouseLocationService $updateService,
        private readonly DeleteWarehouseLocationService $deleteService,
    ) {}

    public function index(ListWarehouseLocationRequest $request): JsonResponse
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
            'data' => WarehouseLocationResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|WarehouseLocationResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new WarehouseLocationResource($result->valueOrFail());
    }

    public function store(UpsertWarehouseLocationRequest $request): JsonResponse|WarehouseLocationResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new WarehouseLocationResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertWarehouseLocationRequest $request, int|string $id): JsonResponse|WarehouseLocationResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'WAREHOUSE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new WarehouseLocationResource($result->valueOrFail());
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
