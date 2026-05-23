<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Vehicle\Application\DTOs\VehicleData;
use Modules\Vehicle\Application\Services\VehicleService;
use Modules\Vehicle\Domain\Exceptions\VehicleRecordNotFoundException;
use Modules\Vehicle\Presentation\Http\Controllers\Concerns\HandlesVehicleHttp;
use Modules\Vehicle\Presentation\Http\Requests\StoreVehicleRequest;
use Modules\Vehicle\Presentation\Http\Requests\UpdateVehicleRequest;
use Modules\Vehicle\Presentation\Http\Resources\VehicleResource;

class VehicleController extends Controller
{
    use HandlesVehicleHttp;

    public function __construct(private readonly VehicleService $vehicles)
    {
    }

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return VehicleResource::collection($this->vehicles->listVehicles(
                $tenant,
                $this->filters(
                    $request,
                    ['organization_unit_id', 'status', 'usage_profile', 'category', 'make', 'model'],
                ),
                $this->perPage($request),
            ));
        } catch (VehicleRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreVehicleRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $vehicle = $this->vehicles->createVehicle(VehicleData::fromArray($tenant, $request->validated()));

            return (new VehicleResource($vehicle))->response()->setStatusCode(201);
        } catch (VehicleRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $vehicle): VehicleResource|JsonResponse
    {
        try {
            return new VehicleResource($this->vehicles->findVehicle($tenant, $vehicle));
        } catch (VehicleRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(
        UpdateVehicleRequest $request,
        int|string $tenant,
        int|string $vehicle,
    ): VehicleResource|JsonResponse {
        try {
            return new VehicleResource(
                $this->vehicles->updateVehicle(
                    $tenant,
                    $vehicle,
                    VehicleData::fromArray($tenant, $request->validated()),
                ),
            );
        } catch (VehicleRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $vehicle): JsonResponse
    {
        try {
            $this->vehicles->deleteVehicle($tenant, $vehicle);

            return response()->json(null, 204);
        } catch (VehicleRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
