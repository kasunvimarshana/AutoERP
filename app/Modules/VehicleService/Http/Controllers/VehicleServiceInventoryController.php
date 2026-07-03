<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleService\Http\Requests\IssueVehicleServiceInventoryRequest;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceInventoryMovementResource;
use Modules\VehicleService\Http\Resources\VehicleServiceJobLineResource;
use Modules\VehicleService\Services\VehicleServiceInventoryIntegrationService;

final class VehicleServiceInventoryController extends VehicleServiceController
{
    public function issue(
        IssueVehicleServiceInventoryRequest $request,
        int $job,
        VehicleServiceInventoryIntegrationService $service,
    ): JsonResponse {
        $movements = $service->issue(
            $this->job($request, $job),
            (int) $request->input('warehouse_id'),
            $request->filled('warehouse_location_id')
                ? (int) $request->input('warehouse_location_id')
                : null,
            array_map('intval', $request->input('line_ids', [])),
            $request->currentUserId(),
            $request->expectedVersion(),
        );

        return response()->json([
            'data' => VehicleServiceInventoryMovementResource::collection($movements)->resolve($request),
        ]);
    }

    public function lines(
        ListVehicleServiceJobRequest $request,
        int $job,
        VehicleServiceInventoryIntegrationService $service,
    ): AnonymousResourceCollection {
        return VehicleServiceJobLineResource::collection($service->issueLines(
            $this->job($request, $job),
            $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null,
            $request->filled('warehouse_location_id')
                ? (int) $request->input('warehouse_location_id')
                : null,
        ));
    }
}
