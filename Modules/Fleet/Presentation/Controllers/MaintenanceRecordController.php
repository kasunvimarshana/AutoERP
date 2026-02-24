<?php

namespace Modules\Fleet\Presentation\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Fleet\Application\UseCases\LogMaintenanceUseCase;
use Modules\Fleet\Domain\Contracts\MaintenanceRecordRepositoryInterface;
use Modules\Fleet\Presentation\Requests\StoreMaintenanceRecordRequest;

class MaintenanceRecordController extends Controller
{
    public function __construct(
        private MaintenanceRecordRepositoryInterface $maintenanceRepo,
        private LogMaintenanceUseCase                $logUseCase,
    ) {}

    public function index(string $vehicleId): JsonResponse
    {
        return response()->json($this->maintenanceRepo->findByVehicle($vehicleId));
    }

    public function store(StoreMaintenanceRecordRequest $request, string $vehicleId): JsonResponse
    {
        $record = $this->logUseCase->execute(
            array_merge($request->validated(), [
                'tenant_id'  => auth()->user()?->tenant_id,
                'vehicle_id' => $vehicleId,
            ])
        );

        return response()->json($record, 201);
    }

    public function show(string $vehicleId, string $id): JsonResponse
    {
        $record = $this->maintenanceRepo->findById($id);

        if (! $record) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($record);
    }

    public function destroy(string $vehicleId, string $id): JsonResponse
    {
        $this->maintenanceRepo->delete($id);

        return response()->json(null, 204);
    }
}
