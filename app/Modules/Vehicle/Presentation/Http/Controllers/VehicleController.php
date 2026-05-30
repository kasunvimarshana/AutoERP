<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Results\Error;
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
    ) {}

    public function index(ListVehicleRequest $request): JsonResponse
    {
        $result = $this->listVehicles->execute($request->validated());

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
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
            return $this->errorResponse($result->errorOrFail());
        }

        return new VehicleResource($result->valueOrFail());
    }

    public function store(UpsertVehicleRequest $request): JsonResponse|VehicleResource
    {
        $result = $this->createVehicle->execute($request->validated());

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return (new VehicleResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertVehicleRequest $request, int|string $vehicle): JsonResponse|VehicleResource
    {
        $result = $this->updateVehicle->execute($vehicle, $request->validated());

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return new VehicleResource($result->valueOrFail());
    }

    public function destroy(int|string $vehicle): JsonResponse
    {
        $result = $this->deleteVehicle->execute($vehicle);

        if ($result->isFailure()) {
            return $this->errorResponse($result->errorOrFail());
        }

        return response()->json(null, 204);
    }

    private function errorResponse(Error $error): JsonResponse
    {
        $status = $error->code === 'VEHICLE_NOT_FOUND' ? 404 : 422;

        return \api_error_response($error->code, $error->message, $status, 'domain', $error->context);
    }
}
