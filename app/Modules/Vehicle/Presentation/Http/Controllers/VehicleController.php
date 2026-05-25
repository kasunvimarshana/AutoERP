<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\CreateVehicleServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\DeleteVehicleServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\GetVehicleServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\ListVehiclesServiceInterface;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\UpdateVehicleServiceInterface;
use Modules\Vehicle\Presentation\Http\Requests\ListVehicleRequest;
use Modules\Vehicle\Presentation\Http\Requests\UpsertVehicleRequest;
use Modules\Vehicle\Presentation\Http\Resources\VehicleResource;

final class VehicleController extends Controller
{
    public function __construct(
        private readonly ListVehiclesServiceInterface $listVehicles,
        private readonly GetVehicleServiceInterface $getVehicle,
        private readonly CreateVehicleServiceInterface $createVehicle,
        private readonly UpdateVehicleServiceInterface $updateVehicle,
        private readonly DeleteVehicleServiceInterface $deleteVehicle,
    ) {
    }

    public function index(ListVehicleRequest $request): JsonResponse
    {
        $result = $this->listVehicles->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => VehicleResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $vehicle): JsonResponse|VehicleResource
    {
        $result = $this->getVehicle->execute($vehicle);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new VehicleResource($result->valueOrFail());
    }

    public function store(UpsertVehicleRequest $request): JsonResponse|VehicleResource
    {
        $result = $this->createVehicle->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new VehicleResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleRequest $request, int|string $vehicle): JsonResponse|VehicleResource
    {
        $result = $this->updateVehicle->execute($vehicle, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'VEHICLE_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new VehicleResource($result->valueOrFail());
    }

    public function destroy(int|string $vehicle): JsonResponse
    {
        $result = $this->deleteVehicle->execute($vehicle);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
