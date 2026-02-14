<?php

namespace App\Modules\Fleet\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Fleet\Services\FleetVehicleService;
use App\Modules\Fleet\Requests\CreateFleetVehicleRequest;
use App\Modules\Fleet\Requests\UpdateFleetVehicleRequest;
use App\Modules\Fleet\Resources\FleetVehicleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FleetVehicleController extends BaseController
{
    protected FleetVehicleService $service;

    public function __construct(FleetVehicleService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $vehicles = $this->service->getAllFleetVehicles();
        return $this->collection(FleetVehicleResource::collection($vehicles));
    }

    public function store(CreateFleetVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->service->createFleetVehicle($request->validated());
        return $this->created(new FleetVehicleResource($vehicle));
    }

    public function show(int $id): JsonResponse
    {
        $vehicle = $this->service->getFleetVehicle($id);
        if (!$vehicle) {
            return $this->notFound();
        }
        return $this->resource(new FleetVehicleResource($vehicle));
    }

    public function update(UpdateFleetVehicleRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateFleetVehicle($id, $request->validated());
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Fleet Vehicle updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteFleetVehicle($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->noContent();
    }

    public function getByStatus(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $vehicles = $this->service->getByStatus($status);
        return $this->collection(FleetVehicleResource::collection($vehicles));
    }

    public function getByFuelType(Request $request): JsonResponse
    {
        $fuelType = $request->input('fuel_type');
        $vehicles = $this->service->getByFuelType($fuelType);
        return $this->collection(FleetVehicleResource::collection($vehicles));
    }

    public function getByMake(Request $request): JsonResponse
    {
        $make = $request->input('make');
        $vehicles = $this->service->getByMake($make);
        return $this->collection(FleetVehicleResource::collection($vehicles));
    }

    public function getByYear(Request $request): JsonResponse
    {
        $year = $request->input('year');
        $vehicles = $this->service->getByYear($year);
        return $this->collection(FleetVehicleResource::collection($vehicles));
    }

    public function getServiceDue(Request $request): JsonResponse
    {
        $date = $request->input('date');
        $vehicles = $this->service->getServiceDue($date);
        return $this->collection(FleetVehicleResource::collection($vehicles));
    }

    public function updateMileage(Request $request, int $id): JsonResponse
    {
        $request->validate(['mileage' => 'required|integer|min:0']);
        $result = $this->service->updateMileage($id, $request->input('mileage'));
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Vehicle mileage updated successfully');
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|string|in:active,maintenance,retired']);
        $result = $this->service->updateStatus($id, $request->input('status'));
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Vehicle status updated successfully');
    }

    public function recordService(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'service_date' => 'required|date',
            'next_service_due' => 'nullable|date',
        ]);
        
        $result = $this->service->recordService(
            $id,
            $request->input('service_date'),
            $request->input('next_service_due')
        );
        
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Vehicle service recorded successfully');
    }

    public function getByTenantAndStatus(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $status = $request->input('status');
        $vehicles = $this->service->getByTenantAndStatus($tenantId, $status);
        return $this->collection(FleetVehicleResource::collection($vehicles));
    }

    public function getTotalMileage(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $total = $this->service->getTotalMileage($tenantId);
        return $this->success(['total_mileage' => $total]);
    }

    public function getVehicleCount(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $status = $request->input('status');
        $count = $this->service->getVehicleCount($tenantId, $status);
        return $this->success(['vehicle_count' => $count]);
    }
}
