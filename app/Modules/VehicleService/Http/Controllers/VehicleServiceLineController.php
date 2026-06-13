<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceLineRequest;
use Modules\VehicleService\Http\Requests\VehicleServiceActionRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceJobLineResource;
use Modules\VehicleService\Services\VehicleServiceLineService;

final class VehicleServiceLineController extends VehicleServiceController
{
    public function index(ListVehicleServiceJobRequest $request, int $job): AnonymousResourceCollection
    {
        return VehicleServiceJobLineResource::collection(
            $this->job($request, $job)->lines()
                ->whereNull('parent_line_id')
                ->with([
                    'item',
                    'variant',
                    'uom',
                    'children.item',
                    'children.uom',
                    'employeeAssignments.employee',
                ])
                ->get(),
        );
    }

    public function store(
        StoreVehicleServiceLineRequest $request,
        int $job,
        VehicleServiceLineService $service,
    ): JsonResponse {
        return (new VehicleServiceJobLineResource(
            $service->create($this->job($request, $job), $request->toData()),
        ))->response()->setStatusCode(201);
    }

    public function update(
        StoreVehicleServiceLineRequest $request,
        int $job,
        int $line,
        VehicleServiceLineService $service,
    ): VehicleServiceJobLineResource {
        $jobModel = $this->job($request, $job);

        return new VehicleServiceJobLineResource(
            $service->update($jobModel, $this->line($jobModel, $line), $request->toData()),
        );
    }

    public function destroy(
        VehicleServiceActionRequest $request,
        int $job,
        int $line,
        VehicleServiceLineService $service,
    ): JsonResponse {
        $jobModel = $this->job($request, $job);
        $service->delete($jobModel, $this->line($jobModel, $line));

        return response()->json(status: 204);
    }
}
