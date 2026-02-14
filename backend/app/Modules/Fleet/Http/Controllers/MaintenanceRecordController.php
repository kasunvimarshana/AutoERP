<?php

namespace App\Modules\Fleet\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Fleet\Services\MaintenanceRecordService;
use App\Modules\Fleet\Requests\CreateMaintenanceRecordRequest;
use App\Modules\Fleet\Requests\UpdateMaintenanceRecordRequest;
use App\Modules\Fleet\Resources\MaintenanceRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceRecordController extends BaseController
{
    protected MaintenanceRecordService $service;

    public function __construct(MaintenanceRecordService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $records = $this->service->getAllMaintenanceRecords();
        return $this->collection(MaintenanceRecordResource::collection($records));
    }

    public function store(CreateMaintenanceRecordRequest $request): JsonResponse
    {
        $record = $this->service->createMaintenanceRecord($request->validated());
        return $this->created(new MaintenanceRecordResource($record));
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->service->getMaintenanceRecord($id);
        if (!$record) {
            return $this->notFound();
        }
        return $this->resource(new MaintenanceRecordResource($record));
    }

    public function update(UpdateMaintenanceRecordRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateMaintenanceRecord($id, $request->validated());
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Maintenance Record updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteMaintenanceRecord($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->noContent();
    }

    public function getByFleetVehicle(int $vehicleId): JsonResponse
    {
        $records = $this->service->getByFleetVehicle($vehicleId);
        return $this->collection(MaintenanceRecordResource::collection($records));
    }

    public function getByMaintenanceType(Request $request): JsonResponse
    {
        $maintenanceType = $request->input('maintenance_type');
        $records = $this->service->getByMaintenanceType($maintenanceType);
        return $this->collection(MaintenanceRecordResource::collection($records));
    }

    public function getByStatus(Request $request): JsonResponse
    {
        $status = $request->input('status');
        $records = $this->service->getByStatus($status);
        return $this->collection(MaintenanceRecordResource::collection($records));
    }

    public function getByDateRange(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $records = $this->service->getByDateRange($startDate, $endDate);
        return $this->collection(MaintenanceRecordResource::collection($records));
    }

    public function getTotalCost(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $total = $this->service->getTotalCost($tenantId, $startDate, $endDate);
        return $this->success(['total_cost' => number_format((float) $total, 2, '.', '')]);
    }

    public function getCostByVehicle(Request $request, int $vehicleId): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $total = $this->service->getCostByVehicle($vehicleId, $startDate, $endDate);
        return $this->success(['total_cost' => number_format((float) $total, 2, '.', '')]);
    }

    public function getCostByMaintenanceType(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $costs = $this->service->getCostByMaintenanceType($tenantId, $startDate, $endDate);
        return $this->success(['cost_by_maintenance_type' => $costs]);
    }

    public function getUpcomingMaintenance(Request $request): JsonResponse
    {
        $date = $request->input('date');
        $records = $this->service->getUpcomingMaintenance($date);
        return $this->collection(MaintenanceRecordResource::collection($records));
    }

    public function getRecentMaintenance(Request $request, int $vehicleId): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $records = $this->service->getRecentMaintenance($vehicleId, $limit);
        return $this->collection(MaintenanceRecordResource::collection($records));
    }

    public function complete(int $id): JsonResponse
    {
        $result = $this->service->completeMaintenanceRecord($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Maintenance Record completed successfully');
    }

    public function cancel(int $id): JsonResponse
    {
        $result = $this->service->cancelMaintenanceRecord($id);
        if (!$result) {
            return $this->notFound();
        }
        return $this->success(null, 'Maintenance Record cancelled successfully');
    }
}
