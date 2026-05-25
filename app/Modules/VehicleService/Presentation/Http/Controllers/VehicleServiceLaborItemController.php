<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\CreateVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\DeleteVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\GetVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\ListVehicleServiceLaborItemsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborItems\UpdateVehicleServiceLaborItemServiceInterface;
use Modules\VehicleService\Presentation\Http\Requests\ListVehicleServiceLaborItemRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertVehicleServiceLaborItemRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceLaborItemResource;

final class VehicleServiceLaborItemController extends Controller
{
    public function __construct(
        private readonly ListVehicleServiceLaborItemsServiceInterface $listService,
        private readonly GetVehicleServiceLaborItemServiceInterface $getService,
        private readonly CreateVehicleServiceLaborItemServiceInterface $createService,
        private readonly UpdateVehicleServiceLaborItemServiceInterface $updateService,
        private readonly DeleteVehicleServiceLaborItemServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleServiceLaborItemRequest $request): JsonResponse
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
            'data' => VehicleServiceLaborItemResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleServiceLaborItemResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleServiceLaborItemResource($result->valueOrFail());
    }

    public function store(UpsertVehicleServiceLaborItemRequest $request): JsonResponse|VehicleServiceLaborItemResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleServiceLaborItemResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleServiceLaborItemRequest $request, int|string $id): JsonResponse|VehicleServiceLaborItemResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleServiceLaborItemResource($result->valueOrFail());
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