<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\CreateVehicleServiceInspectionLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\DeleteVehicleServiceInspectionLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\GetVehicleServiceInspectionLineServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\ListVehicleServiceInspectionLinesServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceInspectionLines\UpdateVehicleServiceInspectionLineServiceInterface;
use Modules\VehicleService\Presentation\Http\Requests\ListVehicleServiceInspectionLineRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertVehicleServiceInspectionLineRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceInspectionLineResource;

final class VehicleServiceInspectionLineController extends Controller
{
    public function __construct(
        private readonly ListVehicleServiceInspectionLinesServiceInterface $listService,
        private readonly GetVehicleServiceInspectionLineServiceInterface $getService,
        private readonly CreateVehicleServiceInspectionLineServiceInterface $createService,
        private readonly UpdateVehicleServiceInspectionLineServiceInterface $updateService,
        private readonly DeleteVehicleServiceInspectionLineServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleServiceInspectionLineRequest $request): JsonResponse
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
            'data' => VehicleServiceInspectionLineResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleServiceInspectionLineResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleServiceInspectionLineResource($result->valueOrFail());
    }

    public function store(UpsertVehicleServiceInspectionLineRequest $request): JsonResponse|VehicleServiceInspectionLineResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleServiceInspectionLineResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleServiceInspectionLineRequest $request, int|string $id): JsonResponse|VehicleServiceInspectionLineResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleServiceInspectionLineResource($result->valueOrFail());
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