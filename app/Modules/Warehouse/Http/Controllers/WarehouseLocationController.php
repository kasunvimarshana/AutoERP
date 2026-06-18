<?php

declare(strict_types=1);

namespace Modules\Warehouse\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Http\Requests\ListWarehouseLocationRequest;
use Modules\Warehouse\Http\Requests\UpsertWarehouseLocationRequest;
use Modules\Warehouse\Http\Resources\WarehouseLocationResource;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseAuthorizationService;
use Modules\Warehouse\Services\WarehouseDefaultResolver;
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
        private readonly WarehouseAuthorizationService $authorization,
        private readonly WarehouseDefaultResolver $defaults,
    ) {}

    public function index(ListWarehouseLocationRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::LOCATIONS_VIEW);

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
            'data' => WarehouseLocationResource::collection($pageResult->items)->resolve(),
            'meta' => $pageResult->paginationMeta(),
        ]);
    }

    public function show(ListWarehouseLocationRequest $request, int|string $id): JsonResponse|WarehouseLocationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::LOCATIONS_VIEW);

        $result = $this->getService->execute($id, $request->tenantId(), $request->organizationUnitId());

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return new WarehouseLocationResource($result->valueOrFail());
    }

    public function store(UpsertWarehouseLocationRequest $request): JsonResponse|WarehouseLocationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::LOCATIONS_CREATE);
        if ($request->boolean('is_default')) {
            $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::LOCATIONS_MANAGE_DEFAULTS);
        }

        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return (new WarehouseLocationResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertWarehouseLocationRequest $request, int|string $id): JsonResponse|WarehouseLocationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::LOCATIONS_UPDATE);
        if ($request->has('is_default')) {
            $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::LOCATIONS_MANAGE_DEFAULTS);
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

        return new WarehouseLocationResource($result->valueOrFail());
    }

    public function destroy(ListWarehouseLocationRequest $request, int|string $id): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::LOCATIONS_DELETE);

        $result = $this->deleteService->execute($id, $request->tenantId(), $request->organizationUnitId());

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return response()->json(null, 204);
    }

    public function activate(ListWarehouseLocationRequest $request, int|string $warehouseLocation): JsonResponse|WarehouseLocationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::LOCATIONS_ACTIVATE);

        return $this->activeResponse($this->updateService->setActive($warehouseLocation, $request->tenantId(), $request->organizationUnitId(), true));
    }

    public function deactivate(ListWarehouseLocationRequest $request, int|string $warehouseLocation): JsonResponse|WarehouseLocationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::LOCATIONS_DEACTIVATE);

        return $this->activeResponse($this->updateService->setActive($warehouseLocation, $request->tenantId(), $request->organizationUnitId(), false));
    }

    public function defaultLocation(ListWarehouseLocationRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), WarehouseAuthorizationService::LOCATIONS_VIEW);
        $warehouseId = $request->integer('warehouse_id');
        if ($warehouseId < 1) {
            return response()->json([
                'message' => 'Warehouse is required to resolve a default location.',
                'errors' => ['warehouse_id' => ['Warehouse is required to resolve a default location.']],
            ], 422);
        }

        $warehouse = WarehouseModel::query()
            ->forTenant($request->tenantId(), $request->organizationUnitId())
            ->find($warehouseId);
        if (! $warehouse instanceof WarehouseModel) {
            return response()->json(['message' => 'Warehouse not found.'], 404);
        }

        $location = $this->defaults->resolveDefaultLocation($warehouse);

        return response()->json([
            'data' => $location instanceof WarehouseLocationModel
                ? (new WarehouseLocationResource($location->load(['warehouse', 'parent', 'organizationUnit'])))->resolve($request)
                : null,
        ]);
    }

    private function activeResponse(Result $result): JsonResponse|WarehouseLocationResource
    {
        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return new WarehouseLocationResource($result->valueOrFail());
    }

    private function errorResponse(Error $error): JsonResponse
    {
        $status = match ($error->code) {
            'WAREHOUSE_NOT_FOUND', 'WAREHOUSE_LOCATION_NOT_FOUND' => 404,
            'WAREHOUSE_STALE_RECORD' => 409,
            default => 422,
        };

        return response()->json(['message' => $error->message], $status);
    }
}
