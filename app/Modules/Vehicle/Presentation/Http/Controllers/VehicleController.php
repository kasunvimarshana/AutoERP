<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Vehicle\Application\Services\VehicleService;
use Modules\Vehicle\Presentation\Http\Requests\ListVehicleRequest;
use Modules\Vehicle\Presentation\Http\Requests\UpsertVehicleRequest;
use Modules\Vehicle\Presentation\Http\Resources\VehicleListResource;
use Modules\Vehicle\Presentation\Http\Resources\VehicleResource;

final class VehicleController extends Controller
{
    public function __construct(private readonly VehicleService $vehicles) {}

    public function index(ListVehicleRequest $request): AnonymousResourceCollection
    {
        return VehicleListResource::collection($this->vehicles->paginate($request->validated()));
    }

    public function show(int $vehicle): VehicleResource
    {
        return new VehicleResource($this->vehicles->find($vehicle));
    }

    public function store(UpsertVehicleRequest $request): JsonResponse
    {
        return (new VehicleResource($this->vehicles->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpsertVehicleRequest $request, int $vehicle): VehicleResource
    {
        return new VehicleResource($this->vehicles->update($vehicle, $request->validated()));
    }

    public function destroy(int $vehicle): JsonResponse
    {
        $this->vehicles->delete($vehicle);

        return response()->json(null, 204);
    }
}
