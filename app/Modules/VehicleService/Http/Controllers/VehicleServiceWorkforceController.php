<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceEmployeeRequest;
use Modules\VehicleService\Http\Requests\VehicleServiceActionRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceEmployeeAssignmentResource;
use Modules\VehicleService\Http\Resources\VehicleServiceJobLineResource;
use Modules\VehicleService\Services\VehicleServiceCommissionPolicyService;
use Modules\VehicleService\Services\VehicleServiceEmployeeAssignmentService;

final class VehicleServiceWorkforceController extends VehicleServiceController
{
    public function index(
        ListVehicleServiceJobRequest $request,
        int $job,
        int $line,
    ): AnonymousResourceCollection {
        $jobModel = $this->job($request, $job);

        return VehicleServiceEmployeeAssignmentResource::collection(
            $this->line($jobModel, $line)->employeeAssignments()->with('employee')->get(),
        );
    }

    public function store(
        StoreVehicleServiceEmployeeRequest $request,
        int $job,
        int $line,
        VehicleServiceEmployeeAssignmentService $service,
    ): JsonResponse {
        $jobModel = $this->job($request, $job);

        return (new VehicleServiceEmployeeAssignmentResource($service->create(
            $jobModel,
            $this->line($jobModel, $line),
            $request->toData(),
            $request->expectedVersion(),
        )))->response()->setStatusCode(201);
    }

    public function update(
        StoreVehicleServiceEmployeeRequest $request,
        int $job,
        int $line,
        int $assignment,
        VehicleServiceEmployeeAssignmentService $service,
    ): VehicleServiceEmployeeAssignmentResource {
        $jobModel = $this->job($request, $job);
        $lineModel = $this->line($jobModel, $line);

        return new VehicleServiceEmployeeAssignmentResource($service->update(
            $jobModel,
            $lineModel,
            $this->assignment($lineModel, $assignment),
            $request->toData(),
            $request->expectedVersion(),
        ));
    }

    public function destroy(
        VehicleServiceActionRequest $request,
        int $job,
        int $line,
        int $assignment,
        VehicleServiceEmployeeAssignmentService $service,
    ): JsonResponse {
        $jobModel = $this->job($request, $job);
        $lineModel = $this->line($jobModel, $line);
        $service->delete($jobModel, $lineModel, $this->assignment($lineModel, $assignment), $request->expectedVersion());

        return response()->json(status: 204);
    }

    public function assignableLines(
        ListVehicleServiceJobRequest $request,
        int $job,
        VehicleServiceCommissionPolicyService $commissionPolicies,
    ): AnonymousResourceCollection {
        $jobModel = $this->job($request, $job);
        $lines = $jobModel->lines()
            ->where('is_employee_assignable', true)
            ->with(['item', 'variant', 'uom', 'employeeAssignments.employee'])
            ->get();
        $itemIds = $lines->pluck('item_id')->filter()->map(static fn ($id): int => (int) $id)->values()->all();
        $defaults = $commissionPolicies->laborDefaultsForItems(
            $request->tenantId(),
            (int) $request->organizationUnitId(),
            $itemIds,
        );

        foreach ($lines as $line) {
            $line->setAttribute('commission_default', $defaults[(int) $line->item_id] ?? null);
        }

        return VehicleServiceJobLineResource::collection($lines);
    }
}
