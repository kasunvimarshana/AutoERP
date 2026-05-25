<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\CreateVehicleServiceTypeServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\DeleteVehicleServiceTypeServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\GetVehicleServiceTypeServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\ListVehicleServiceTypesServiceInterface;
use Modules\VehicleService\Application\Contracts\UseCases\VehicleServiceTypes\UpdateVehicleServiceTypeServiceInterface;
use Modules\VehicleService\Presentation\Http\Requests\ListVehicleServiceTypeRequest;
use Modules\VehicleService\Presentation\Http\Requests\UpsertVehicleServiceTypeRequest;
use Modules\VehicleService\Presentation\Http\Resources\VehicleServiceTypeResource;

final class VehicleServiceTypeController extends Controller
{
    public function __construct(
        private readonly ListVehicleServiceTypesServiceInterface $listService,
        private readonly GetVehicleServiceTypeServiceInterface $getService,
        private readonly CreateVehicleServiceTypeServiceInterface $createService,
        private readonly UpdateVehicleServiceTypeServiceInterface $updateService,
        private readonly DeleteVehicleServiceTypeServiceInterface $deleteService,
    ) {
    }

    public function index(ListVehicleServiceTypeRequest $request): JsonResponse
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
            'data' => VehicleServiceTypeResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|VehicleServiceTypeResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleServiceTypeResource($result->valueOrFail());
    }

    public function store(UpsertVehicleServiceTypeRequest $request): JsonResponse|VehicleServiceTypeResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleServiceTypeResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleServiceTypeRequest $request, int|string $id): JsonResponse|VehicleServiceTypeResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLESERVICE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleServiceTypeResource($result->valueOrFail());
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