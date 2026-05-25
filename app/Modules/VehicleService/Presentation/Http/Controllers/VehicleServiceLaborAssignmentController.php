<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\CreateVehicleServiceLaborAssignmentServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\DeleteVehicleServiceLaborAssignmentServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\GetVehicleServiceLaborAssignmentServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\ListVehicleServiceLaborAssignmentsServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceLaborAssignments\UpdateVehicleServiceLaborAssignmentServiceInterface;
use Modules\VehicleService\Presentation\Http\Requests\ListVehicleServiceLaborAssignmentRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertVehicleServiceLaborAssignmentRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceLaborAssignmentResource;

final class VehicleServiceLaborAssignmentController extends Controller
{
    public function __construct(
        private readonly ListVehicleServiceLaborAssignmentsServiceInterface $listService,
        private readonly GetVehicleServiceLaborAssignmentServiceInterface $getService,
        private readonly CreateVehicleServiceLaborAssignmentServiceInterface $createService,
        private readonly UpdateVehicleServiceLaborAssignmentServiceInterface $updateService,
        private readonly DeleteVehicleServiceLaborAssignmentServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleServiceLaborAssignmentRequest $request): JsonResponse
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
            'data' => VehicleServiceLaborAssignmentResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleServiceLaborAssignmentResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleServiceLaborAssignmentResource($result->valueOrFail());
    }

    public function store(UpsertVehicleServiceLaborAssignmentRequest $request): JsonResponse|VehicleServiceLaborAssignmentResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleServiceLaborAssignmentResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleServiceLaborAssignmentRequest $request, int|string $id): JsonResponse|VehicleServiceLaborAssignmentResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleServiceLaborAssignmentResource($result->valueOrFail());
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