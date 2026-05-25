<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\CreateVehicleServiceInspectionServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\DeleteVehicleServiceInspectionServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\GetVehicleServiceInspectionServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\ListVehicleServiceInspectionsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspections\UpdateVehicleServiceInspectionServiceInterface;
use Modules\VehicleService\Presentation\Http\Requests\ListVehicleServiceInspectionRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertVehicleServiceInspectionRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceInspectionResource;

final class VehicleServiceInspectionController extends Controller
{
    public function __construct(
        private readonly ListVehicleServiceInspectionsServiceInterface $listService,
        private readonly GetVehicleServiceInspectionServiceInterface $getService,
        private readonly CreateVehicleServiceInspectionServiceInterface $createService,
        private readonly UpdateVehicleServiceInspectionServiceInterface $updateService,
        private readonly DeleteVehicleServiceInspectionServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleServiceInspectionRequest $request): JsonResponse
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
            'data' => VehicleServiceInspectionResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleServiceInspectionResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleServiceInspectionResource($result->valueOrFail());
    }

    public function store(UpsertVehicleServiceInspectionRequest $request): JsonResponse|VehicleServiceInspectionResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleServiceInspectionResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleServiceInspectionRequest $request, int|string $id): JsonResponse|VehicleServiceInspectionResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleServiceInspectionResource($result->valueOrFail());
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