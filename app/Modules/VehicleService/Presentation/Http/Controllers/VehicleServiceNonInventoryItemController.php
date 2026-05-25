<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\CreateVehicleServiceNonInventoryItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\DeleteVehicleServiceNonInventoryItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\GetVehicleServiceNonInventoryItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\ListVehicleServiceNonInventoryItemsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceNonInventoryItems\UpdateVehicleServiceNonInventoryItemServiceInterface;
use Modules\VehicleService\Presentation\Http\Requests\ListVehicleServiceNonInventoryItemRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertVehicleServiceNonInventoryItemRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceNonInventoryItemResource;

final class VehicleServiceNonInventoryItemController extends Controller
{
    public function __construct(
        private readonly ListVehicleServiceNonInventoryItemsServiceInterface $listService,
        private readonly GetVehicleServiceNonInventoryItemServiceInterface $getService,
        private readonly CreateVehicleServiceNonInventoryItemServiceInterface $createService,
        private readonly UpdateVehicleServiceNonInventoryItemServiceInterface $updateService,
        private readonly DeleteVehicleServiceNonInventoryItemServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleServiceNonInventoryItemRequest $request): JsonResponse
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
            'data' => VehicleServiceNonInventoryItemResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleServiceNonInventoryItemResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleServiceNonInventoryItemResource($result->valueOrFail());
    }

    public function store(UpsertVehicleServiceNonInventoryItemRequest $request): JsonResponse|VehicleServiceNonInventoryItemResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleServiceNonInventoryItemResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleServiceNonInventoryItemRequest $request, int|string $id): JsonResponse|VehicleServiceNonInventoryItemResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleServiceNonInventoryItemResource($result->valueOrFail());
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