<?php

namespace Modules\Fleet\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Fleet\Application\UseCases\RegisterVehicleUseCase;
use Modules\Fleet\Application\UseCases\RetireVehicleUseCase;
use Modules\Fleet\Domain\Contracts\VehicleRepositoryInterface;
use Modules\Fleet\Presentation\Requests\StoreVehicleRequest;

class VehicleController extends Controller
{
    public function __construct(
        private VehicleRepositoryInterface $vehicleRepo,
        private RegisterVehicleUseCase     $registerUseCase,
        private RetireVehicleUseCase       $retireUseCase,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->vehicleRepo->findByTenant(auth()->user()?->tenant_id));
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->registerUseCase->execute(
            array_merge($request->validated(), ['tenant_id' => auth()->user()?->tenant_id])
        );

        return response()->json($vehicle, 201);
    }

    public function show(string $id): JsonResponse
    {
        $vehicle = $this->vehicleRepo->findById($id);

        if (! $vehicle) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($vehicle);
    }

    public function update(StoreVehicleRequest $request, string $id): JsonResponse
    {
        $vehicle = $this->vehicleRepo->update($id, $request->validated());

        return response()->json($vehicle);
    }

    public function retire(string $id): JsonResponse
    {
        $vehicle = $this->retireUseCase->execute($id);

        return response()->json($vehicle);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->vehicleRepo->delete($id);

        return response()->json(null, 204);
    }
}
