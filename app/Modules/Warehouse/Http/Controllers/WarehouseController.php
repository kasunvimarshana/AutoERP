<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Http\Requests\ListWarehouseRequest;
use Modules\Warehouse\Http\Requests\UpsertWarehouseRequest;
use Modules\Warehouse\Http\Resources\WarehouseResource;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseAuthorizationService;
use Modules\Warehouse\Services\WarehouseDefaultResolver;
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
        private readonly WarehouseAuthorizationService $authorization,
        private readonly WarehouseDefaultResolver $defaults,
    ) {}

    public function index(ListWarehouseRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::WAREHOUSES_VIEW);

        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
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
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::WAREHOUSES_VIEW);

        $result = $this->getService->execute($id, $request->tenantId(), $request->organizationUnitId());

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return new WarehouseResource($result->valueOrFail());
    }

    public function store(UpsertWarehouseRequest $request): JsonResponse|WarehouseResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::WAREHOUSES_CREATE);
        if ($request->boolean('is_default')) {
            $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::WAREHOUSES_MANAGE_DEFAULTS);
        }

        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return (new WarehouseResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertWarehouseRequest $request, int|string $id): JsonResponse|WarehouseResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::WAREHOUSES_UPDATE);
        if ($request->has('is_default')) {
            $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::WAREHOUSES_MANAGE_DEFAULTS);
        }

        $result = $this->updateService->execute(
            $id,
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
        );

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return new WarehouseResource($result->valueOrFail());
    }

    public function destroy(ListWarehouseRequest $request, int|string $id): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::WAREHOUSES_DELETE);

        $result = $this->deleteService->execute($id, $request->tenantId(), $request->organizationUnitId());

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return response()->json(null, 204);
    }

    public function activate(ListWarehouseRequest $request, int|string $warehouse): JsonResponse|WarehouseResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::WAREHOUSES_ACTIVATE);

        return $this->activeResponse($this->updateService->setActive($warehouse, $request->tenantId(), $request->organizationUnitId(), true));
    }

    public function deactivate(ListWarehouseRequest $request, int|string $warehouse): JsonResponse|WarehouseResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::WAREHOUSES_DEACTIVATE);

        return $this->activeResponse($this->updateService->setActive($warehouse, $request->tenantId(), $request->organizationUnitId(), false));
    }

    public function defaultWarehouse(ListWarehouseRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::WAREHOUSES_VIEW);

        $warehouse = $this->defaults->resolveDefaultWarehouse($request->tenantId(), $request->organizationUnitId());

        return response()->json([
            'data' => $warehouse instanceof WarehouseModel
                ? (new WarehouseResource($warehouse->load(['organizationUnit', 'defaultLocation'])->loadCount('locations')))->resolve($request)
                : null,
        ]);
    }

    private function activeResponse(Result $result): JsonResponse|WarehouseResource
    {
        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return new WarehouseResource($result->valueOrFail());
    }

    private function errorResponse(Error $error): JsonResponse
    {
        $status = match ($error->code) {
            'WAREHOUSE_NOT_FOUND' => 404,
            'WAREHOUSE_STALE_RECORD' => 409,
            default => 422,
        };

        return response()->json(['message' => $error->message], $status);
    }
}
